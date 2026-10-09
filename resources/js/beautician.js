/**
 * AI Beautician chat panel. Vanilla JS, no framework: sends guest messages to
 * the Laravel backend and renders both plain text and the structured
 * ui_payload the AI's tools attach (service cards, stylist cards with free
 * start times, availability, appointment confirmations, handover notices) as
 * real DOM, not markdown-in-a-bubble.
 */
import { getJson, postJson } from './shared/api.js';
import { escapeHtml } from './shared/dom.js';

function initBeautician() {
    const root = document.getElementById('beautician-app');
    const widget = document.querySelector('[data-chat-widget]');
    if (!root || !widget) return;

    const config = {
        startUrl: root.dataset.startUrl,
        messageUrl: root.dataset.messageUrl,
        historyUrl: root.dataset.historyUrl,
        storageKey: root.dataset.storageKey,
        locale: root.dataset.locale,
        currency: root.dataset.currency,
        homeUrl: root.dataset.homeUrl,
        serviceUrlTemplate: root.dataset.serviceUrl,
        staffUrl: root.dataset.staffUrl,
        appointmentUrlTemplate: root.dataset.appointmentUrl,
        terms: JSON.parse(root.dataset.terms || '{}'),
        labels: {
            placeholder: root.dataset.labelPlaceholder,
            send: root.dataset.labelSend,
            open: root.dataset.labelOpen,
            close: root.dataset.labelClose,
            intro: root.dataset.labelIntro,
            unavailable: root.dataset.labelUnavailable,
            minutes: root.dataset.labelMinutes,
            availableSlots: root.dataset.labelAvailableSlots,
            noAvailability: root.dataset.labelNoAvailability,
            appointmentReceived: root.dataset.labelAppointmentReceived,
            reference: root.dataset.labelReference,
            depositFree: root.dataset.labelDepositFree,
            deposit: root.dataset.labelDeposit,
            thinking: root.dataset.labelThinking,
            handedOver: root.dataset.labelHandedOver,
            statusSent: root.dataset.labelStatusSent,
            statusWaiting: root.dataset.labelStatusWaiting,
            statusReplied: root.dataset.labelStatusReplied,
            draftTitle: root.dataset.labelDraftTitle,
            draftBody: root.dataset.labelDraftBody,
            draftKeep: root.dataset.labelDraftKeep,
            draftDiscard: root.dataset.labelDraftDiscard,
            viewDetails: root.dataset.labelViewDetails,
            serviceDetailsQuestion: root.dataset.labelServiceDetailsQuestion,
            bookNow: root.dataset.labelBookNow,
            bookSlotQuestion: root.dataset.labelBookSlotQuestion,
            startHere: root.dataset.labelStartHere,
            staff: root.dataset.labelStaff,
            error: root.dataset.labelError,
            slow: root.dataset.labelSlow,
            retry: root.dataset.labelRetry,
        },
    };

    const messagesEl = root.querySelector('[data-messages]');
    const formEl = root.querySelector('[data-chat-form]');
    const inputEl = root.querySelector('[data-chat-input]');
    const submitEl = root.querySelector('[data-chat-submit]');
    const statusBanner = root.querySelector('[data-status-banner]');
    const chatStatus = root.querySelector('[data-chat-status]');
    const thinkingIndicator = root.querySelector('[data-thinking-indicator]');
    const draftDialog = root.querySelector('[data-draft-dialog]');
    const draftKeep = root.querySelector('[data-draft-keep]');
    const draftDiscard = root.querySelector('[data-draft-discard]');
    const chatLog = root.querySelector('#beautician-chat-log');
    const closeButton = widget.querySelector('[data-chat-close]');
    const launcher = widget.querySelector('[data-chat-launcher]');

    let ready = false;
    let busy = false;
    let handedOver = false;
    let knownMessageCount = 0;
    let pollTimer = null;
    let isOpen = false;
    let draftDialogOpen = false;

    function setChatStatus(text, tone = '') {
        if (!chatStatus) return;

        chatStatus.textContent = text || '';
        chatStatus.className = `chat-status${text ? ` is-${tone}` : ''}`;
        chatStatus.hidden = !text;
    }

    function showDraftDialog() {
        if (!draftDialog) return;

        draftDialogOpen = true;
        draftDialog.hidden = false;
        draftDialog.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => draftKeep?.focus());
    }

    function hideDraftDialog({ focusInput = false } = {}) {
        if (!draftDialog) return;

        draftDialogOpen = false;
        draftDialog.hidden = true;
        draftDialog.setAttribute('aria-hidden', 'true');
        if (focusInput) inputEl.focus();
    }

    function syncPanelState() {
        root.classList.toggle('is-open', isOpen);
        root.dataset.chatState = isOpen ? 'open' : 'closed';
        chatLog?.setAttribute('aria-hidden', String(!isOpen));
        formEl?.setAttribute('aria-hidden', String(!isOpen));
        launcher?.setAttribute('aria-expanded', String(isOpen));
    }

    function openPanel({ focus = false } = {}) {
        isOpen = true;
        syncPanelState();
        scrollToBottom();

        if (focus) {
            requestAnimationFrame(() => inputEl.focus());
        }
    }

    function finishClosePanel({ restoreFocus = true } = {}) {
        isOpen = false;
        syncPanelState();
        hideDraftDialog();
        if (restoreFocus) launcher?.focus();
    }

    function closePanel({ confirmDraft = true, restoreFocus = true } = {}) {
        if (confirmDraft && inputEl?.value.trim()) {
            showDraftDialog();
            return;
        }

        finishClosePanel({ restoreFocus });
    }

    closeButton?.addEventListener('click', () => closePanel());
    launcher?.addEventListener('click', () => openPanel({ focus: true }));
    draftKeep?.addEventListener('click', () => hideDraftDialog({ focusInput: true }));
    draftDiscard?.addEventListener('click', () => {
        inputEl.value = '';
        finishClosePanel();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        if (draftDialogOpen) {
            hideDraftDialog({ focusInput: true });
            return;
        }

        if (isOpen) closePanel();
    });
    syncPanelState();

    const numberLocale = config.locale === 'ja' ? 'ja-JP' : config.locale === 'en' ? 'en-US' : 'id-ID';

    function money(value) {
        const n = Number(value) || 0;
        return `${config.currency} ${new Intl.NumberFormat(numberLocale, { maximumFractionDigits: 0 }).format(n)}`;
    }

    function formatDate(iso) {
        const [year, month, day] = String(iso).split('-').map(Number);
        if (!year) return iso;
        return new Intl.DateTimeFormat(numberLocale, { weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(year, month - 1, day));
    }

    /** Text from the database is shown as text, never as markup. */
    const esc = escapeHtml;

    function term(group, code) {
        return config.terms[group]?.[code] || String(code).replace(/_/g, ' ');
    }

    // Cloudflare drops a request that is still unanswered after 100s, so give
    // up just before that and let the guest retry instead of waiting forever.
    const API_TIMEOUT_MS = 95000;

    async function api(url, body) {
        const result = await postJson(url, body, { timeoutMs: API_TIMEOUT_MS });

        if (!result.ok) {
            const error = new Error(result.body.message || `Request failed (${result.status})`);
            error.status = result.status;
            throw error;
        }

        return result.body;
    }

    function avatarMark() {
        const el = document.createElement('span');
        el.className = 'chat-host-avatar';
        el.setAttribute('aria-hidden', 'true');
        return el;
    }

    function bubble(role, text) {
        const wrap = document.createElement('div');
        wrap.className = role === 'guest' ? 'cb-row cb-row-guest' : 'cb-row cb-row-host';

        if (role !== 'guest') {
            wrap.appendChild(avatarMark());
        }

        const inner = document.createElement('div');
        inner.className = `cb-bubble cb-bubble-${role === 'guest' ? 'guest' : 'host'}`;
        inner.textContent = text;

        wrap.appendChild(inner);
        return wrap;
    }

    function card(children) {
        const wrap = document.createElement('div');
        wrap.className = 'cb-row cb-row-host';
        const inner = document.createElement('div');
        inner.className = 'cb-stack';
        children.forEach((c) => inner.appendChild(c));
        wrap.appendChild(inner);
        return wrap;
    }

    function pill(text, tone = '') {
        return `<span class="cb-pill${tone ? ` cb-pill-${tone}` : ''}">${esc(text)}</span>`;
    }

    function minutesText(value) {
        return config.labels.minutes.replace(':count', value);
    }

    function depositText(value) {
        return Number(value) > 0 ? `${config.labels.deposit}: ${money(value)}` : config.labels.depositFree;
    }

    function serviceResultCard(service) {
        const el = document.createElement('div');
        el.className = 'cb-card';
        const tags = (service.tags || []).filter((tag) => config.terms.tag?.[tag]).slice(0, 3)
            .map((tag) => pill(term('tag', tag), ['signature', 'bestseller'].includes(tag) ? 'accent' : '')).join('');
        el.innerHTML = `
            ${service.image_url ? `<img src="${esc(service.image_url)}" alt="${esc(service.name)}" class="cb-photo">` : ''}
            <div class="cb-body">
                <div class="cb-head">
                    <p class="cb-title">${esc(service.name)}</p>
                    ${service.is_unavailable ? pill(config.labels.unavailable, 'alert') : ''}
                </div>
                <p class="cb-price">${money(service.price)} <span class="cb-meta">· ${esc(minutesText(service.duration_minutes))}</span></p>
                ${tags ? `<div class="cb-pills">${tags}</div>` : ''}
                <button type="button" class="cb-button" data-detail-slug="${esc(service.service_slug)}">${esc(config.labels.viewDetails)}</button>
            </div>
        `;
        el.querySelector('[data-detail-slug]').addEventListener('click', () => {
            sendMessage(config.labels.serviceDetailsQuestion.replace(':service', service.name));
        });
        return el;
    }

    function serviceDetailCard(service) {
        const el = document.createElement('div');
        el.className = 'cb-card';
        const tags = (service.tags || []).filter((tag) => config.terms.tag?.[tag]).map((tag) => pill(term('tag', tag))).join('');
        const cautions = (service.cautions || []).map((code) => pill(term('caution', code), 'alert')).join('');

        el.innerHTML = `
            ${service.image_url ? `<img src="${esc(service.image_url)}" alt="${esc(service.name)}" class="cb-photo">` : ''}
            <div class="cb-body">
                <div class="cb-head">
                    <p class="cb-title">${esc(service.name)}</p>
                    ${service.is_unavailable ? pill(config.labels.unavailable, 'alert') : ''}
                </div>
                <p class="cb-meta">${esc(service.description || '')}</p>
                <p class="cb-price">${money(service.price)} <span class="cb-meta">· ${esc(minutesText(service.duration_minutes))} · ${esc(depositText(service.deposit))}</span></p>
                ${tags ? `<div class="cb-pills">${tags}</div>` : ''}
                ${cautions ? `<div class="cb-pills">${cautions}</div>` : ''}
            </div>
        `;
        return el;
    }

    function stylistResultCard(stylist, payload) {
        const el = document.createElement('div');
        el.className = 'cb-card';
        const experience = `${stylist.years_experience} ${config.terms.unit?.years || ''}`.trim();
        el.innerHTML = `
            ${stylist.thumbnail_url ? `<img src="${esc(stylist.thumbnail_url)}" alt="${esc(stylist.name)}" class="cb-photo">` : ''}
            <div class="cb-body">
                <p class="cb-title">${esc(stylist.name)}</p>
                <p class="cb-meta">${esc(term('specialty', stylist.specialty))} · ${esc(experience)}</p>
                <p class="cb-label">${esc(config.labels.availableSlots)} · ${esc(formatDate(payload.date))}</p>
                <div class="cb-slots" data-slots></div>
            </div>
        `;
        const slots = el.querySelector('[data-slots]');
        stylist.available_slots.forEach((slot) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'cb-slot';
            button.textContent = slot.time_range;
            button.addEventListener('click', () => {
                sendMessage(config.labels.bookSlotQuestion
                    .replace(':service', payload.service.name)
                    .replace(':stylist', stylist.name)
                    .replace(':date', formatDate(payload.date))
                    .replace(':time', slot.time_range));
            });
            slots.appendChild(button);
        });
        return el;
    }

    function availabilityCard(payload) {
        const el = document.createElement('div');
        el.className = 'cb-card cb-body';

        if (!payload.available) {
            el.innerHTML = `<p class="cb-meta">${esc(config.labels.noAvailability)}</p>`;
            return el;
        }

        const q = payload.quote;
        el.innerHTML = `
            <p class="cb-title">${esc(q.service_name)} · ${esc(q.name)}</p>
            <p class="cb-meta">${esc(formatDate(q.date))} · ${esc(q.time_range)} · ${esc(minutesText(q.duration_minutes))}</p>
            <p class="cb-price">${money(q.price)} <span class="cb-meta">· ${esc(depositText(q.deposit))}</span></p>
        `;
        return el;
    }

    function appointmentConfirmationCard(payload) {
        const r = payload.appointment;
        const el = document.createElement('div');
        el.className = 'cb-card cb-confirm cb-body';
        el.innerHTML = `
            <p class="cb-title">${esc(config.labels.appointmentReceived)}</p>
            <p class="cb-meta">${esc(config.labels.reference)}: ${esc(r.appointment_reference)} · ${esc(r.service_name)} · ${esc(r.name)}</p>
            <p class="cb-meta">${esc(formatDate(r.date))} · ${esc(r.time_range)}</p>
            <p class="cb-price">${esc(depositText(r.deposit_total))}</p>
        `;
        return el;
    }

    function handoverCard() {
        const el = document.createElement('div');
        el.className = 'cb-card cb-handover cb-body';
        el.textContent = config.labels.handedOver;
        return el;
    }

    function renderUiPayload(uiPayload) {
        if (!uiPayload || !uiPayload.length) return;

        uiPayload.forEach((payload) => {
            if (payload.type === 'service_results') {
                messagesEl.appendChild(card(payload.items.map(serviceResultCard)));
            } else if (payload.type === 'service_detail') {
                messagesEl.appendChild(card([serviceDetailCard(payload.item)]));
            } else if (payload.type === 'stylist_results') {
                messagesEl.appendChild(card(payload.stylists.map((stylist) => stylistResultCard(stylist, payload))));
            } else if (payload.type === 'availability') {
                messagesEl.appendChild(card([availabilityCard(payload)]));
            } else if (payload.type === 'appointment_confirmation') {
                messagesEl.appendChild(card([appointmentConfirmationCard(payload)]));
            } else if (payload.type === 'handover') {
                messagesEl.appendChild(card([handoverCard()]));
            }
        });
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function setBusy(value) {
        busy = value;
        const disabled = busy || !ready || handedOver;
        inputEl.disabled = disabled;
        submitEl.disabled = disabled;
        if (launcher) launcher.disabled = busy || !ready;
        root.setAttribute('aria-busy', String(busy));
        document.body.classList.toggle('is-thinking', busy);
        if (thinkingIndicator) {
            thinkingIndicator.hidden = !busy;
        }
        document.querySelectorAll('[data-hero-quick-message], [data-ask-ai-button], [data-quick-message]')
            .forEach((button) => { button.disabled = disabled; });
        submitEl.textContent = busy ? config.labels.thinking : config.labels.send;
        if (busy) scrollToBottom();
    }

    function showHandedOverBanner() {
        handedOver = true;
        statusBanner.textContent = config.labels.handedOver;
        statusBanner.classList.remove('hidden');
        setChatStatus(config.labels.statusWaiting, 'waiting');
        setBusy(busy);
        startPolling();
    }

    function clearHandedOverBanner() {
        handedOver = false;
        statusBanner.classList.add('hidden');
        setBusy(busy);
        stopPolling();
    }

    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(pollForStaffReplies, 4000);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    async function pollForStaffReplies() {
        const guestToken = localStorage.getItem(config.storageKey);
        if (!guestToken) return;

        try {
            const response = await getJson(config.historyUrl, { guest_token: guestToken });
            if (!response.ok) return;

            const data = response.body;

            if (data.messages.length > knownMessageCount) {
                const newMessages = data.messages.slice(knownMessageCount);
                newMessages.forEach(renderMessage);
                if (newMessages.some((message) => message.role === 'staff')) {
                    setChatStatus(config.labels.statusReplied, 'replied');
                }
                window.salonSound?.play('incoming');
                knownMessageCount = data.messages.length;
                scrollToBottom();
            }

            if (data.status !== 'handed_over') {
                clearHandedOverBanner();
            }
        } catch {
            // transient network hiccup — try again on the next tick
        }
    }

    function renderMessage(message) {
        if (message.role === 'guest') {
            messagesEl.appendChild(bubble('guest', message.content));
        } else if (message.role === 'assistant') {
            if (message.content) {
                messagesEl.appendChild(bubble('assistant', message.content));
            }
            renderUiPayload(message.ui_payload);
            const chips = actionChips(message.suggested_actions);
            if (chips) messagesEl.appendChild(chips);
        } else if (message.role === 'staff') {
            messagesEl.appendChild(card([staffBubble(message.content)]));
            setChatStatus(config.labels.statusReplied, 'replied');
        } else if (message.role === 'system') {
            messagesEl.appendChild(bubble('assistant', message.content));
        }
    }

    function quickMenuCard() {
        const template = document.querySelector('[data-quick-services]');
        if (!template) return null;

        const el = document.createElement('div');
        el.className = 'cb-card';

        const heading = document.createElement('p');
        heading.className = 'cb-label cb-list-heading';
        heading.textContent = config.labels.startHere;
        el.appendChild(heading);

        const list = document.createElement('div');
        list.className = 'cb-list';

        template.content.querySelectorAll('[data-quick-message]').forEach((source) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'cb-list-item';
            item.innerHTML = `<span>${esc(source.textContent)}</span><span aria-hidden="true">›</span>`;
            item.addEventListener('click', () => sendMessage(source.dataset.quickMessage));
            list.appendChild(item);
        });

        el.appendChild(list);
        return el;
    }

    function staffBubble(text) {
        const el = document.createElement('div');
        el.className = 'cb-bubble cb-bubble-staff';
        el.innerHTML = `<p class="cb-label">${esc(config.labels.staff)}</p>${esc(text)}`;
        return el;
    }

    const sceneNames = {
        home: 'home', info: 'home', services: 'services', service: 'service', stylists: 'stylists', stylist: 'stylist',
        facilities: 'facilities', facility: 'facility_detail', appointment: 'appointment', staff: 'handover',
    };

    /**
     * Where the guest is in the salon, so the beautician can answer for "this
     * treatment" or the appointment on screen. Never includes name or contact data.
     */
    function uiContext() {
        const pageScene = document.querySelector('[data-stage]')?.dataset.scene || 'home';
        const context = { scene: sceneNames[pageScene] || 'home' };

        const lastSegment = decodeURIComponent(window.location.pathname.split('/').pop());
        if (pageScene === 'service') context.selected_service = lastSegment;
        if (pageScene === 'stylist') context.selected_stylist = lastSegment;
        if (pageScene === 'facility') context.selected_facility = Number(lastSegment);

        if (pageScene === 'appointment') {
            try {
                const draft = JSON.parse(sessionStorage.getItem(`appointment_draft_${root.dataset.salonSlug}`) || 'null');
                const { service_slug, stylist_slug, appointment_date, time_slot, occasion } = draft?.values || {};
                context.appointment = { service_slug, stylist_slug, appointment_date, time_slot, occasion };
                if (service_slug) context.selected_service = service_slug;
                if (stylist_slug) context.selected_stylist = stylist_slug;
            } catch { /* no draft to share */ }
        }

        return context;
    }

    function actionChips(actions) {
        if (!actions || !actions.length) return null;

        const serviceUrl = (slug) => config.serviceUrlTemplate.replace('__SLUG__', encodeURIComponent(slug));
        const appointmentUrl = (service, stylist) => {
            const url = new URL(config.appointmentUrlTemplate, window.location.origin);
            if (service) url.searchParams.set('service', service);
            if (stylist) url.searchParams.set('stylist', stylist);
            return url.toString();
        };
        const targets = {
            view_service: [config.labels.viewDetails, (action) => serviceUrl(action.service)],
            book: [config.labels.bookNow, (action) => appointmentUrl(action.service, action.stylist)],
            staff: [config.labels.staff, () => config.staffUrl],
        };

        const wrap = document.createElement('div');
        wrap.className = 'cb-chips';

        actions.forEach((action) => {
            const target = targets[action.action];
            if (!target) return;
            const link = document.createElement('a');
            link.href = target[1](action);
            link.className = 'cb-chip';
            link.textContent = target[0];
            link.addEventListener('click', (event) => {
                closePanel({ confirmDraft: false, restoreFocus: false });

                // Another page: walk there the way the menu does. A panel on
                // this page is just a hash change, so leave it to the browser.
                if (link.pathname !== window.location.pathname && window.salonStage) {
                    event.preventDefault();
                    window.salonStage.leave(link.href);
                }
            });
            wrap.appendChild(link);
        });

        return wrap.childElementCount ? wrap : null;
    }

    function errorCard(text, err) {
        const el = document.createElement('div');
        el.className = 'cb-card cb-error cb-body';

        const message = document.createElement('p');
        message.textContent = err.status === 429 ? config.labels.slow : config.labels.error;

        const actions = document.createElement('div');
        actions.className = 'cb-chips';

        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'cb-chip cb-chip-solid';
        retry.textContent = config.labels.retry;
        retry.addEventListener('click', () => {
            el.remove();
            sendMessage(text, { retry: true });
        });

        const staff = document.createElement('a');
        staff.href = config.staffUrl;
        staff.className = 'cb-chip';
        staff.textContent = config.labels.staff;
        staff.addEventListener('click', (event) => {
            closePanel({ confirmDraft: false, restoreFocus: false });

            if (window.salonStage) {
                event.preventDefault();
                window.salonStage.leave(staff.href);
            }
        });

        actions.append(retry, staff);
        el.append(message, actions);
        return el;
    }

    async function sendMessage(text, { retry = false } = {}) {
        if (!text.trim() || handedOver || busy || !ready) return;

        const guestToken = localStorage.getItem(config.storageKey);
        if (!guestToken) return;

        openPanel();
        if (!retry) messagesEl.appendChild(bubble('guest', text));
        window.salonSound?.play('sent');
        scrollToBottom();
        setBusy(true);

        try {
            const data = await api(config.messageUrl, { guest_token: guestToken, message: text, ...uiContext() });
            renderMessage(data.message);
            setChatStatus(config.labels.statusSent, 'sent');
            window.salonSound?.play('incoming');
            knownMessageCount += 2; // the guest message just sent + the reply just rendered
            if (data.status === 'handed_over') {
                showHandedOverBanner();
            }
        } catch (err) {
            messagesEl.appendChild(errorCard(text, err));
        } finally {
            setBusy(false);
            scrollToBottom();
        }
    }

    async function boot() {
        let guestToken = localStorage.getItem(config.storageKey);

        if (!guestToken) {
            const data = await api(config.startUrl, { locale: config.locale });
            guestToken = data.guest_token;
            localStorage.setItem(config.storageKey, guestToken);
            messagesEl.appendChild(bubble('assistant', config.labels.intro));
            const menu = quickMenuCard();
            if (menu) messagesEl.appendChild(card([menu]));
            return;
        }

        try {
            const response = await getJson(config.historyUrl, { guest_token: guestToken });

            if (!response.ok) throw new Error('history_unavailable');

            const data = response.body;

            if (!data.messages.length) {
                messagesEl.appendChild(bubble('assistant', config.labels.intro));
                const menu = quickMenuCard();
                if (menu) messagesEl.appendChild(card([menu]));
            } else {
                data.messages.forEach(renderMessage);
            }
            knownMessageCount = data.messages.length;

            if (data.status === 'handed_over') {
                showHandedOverBanner();
            }
            if (data.messages.some((message) => message.role === 'staff')) {
                setChatStatus(config.labels.statusReplied, 'replied');
            }
        } catch {
            localStorage.removeItem(config.storageKey);
            return boot();
        }

        scrollToBottom();
    }

    formEl.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!ready || busy || handedOver || !inputEl.value.trim()) return;
        const text = inputEl.value;
        inputEl.value = '';
        sendMessage(text);
    });

    // Quick-start buttons on a scene — the same topics as the in-chat menu,
    // always visible up front so guests see what the AI can do immediately.
    document.querySelectorAll('[data-hero-quick-message]').forEach((button) => {
        button.addEventListener('click', () => {
            openPanel();
            sendMessage(button.dataset.heroQuickMessage);
        });
    });

    // A menu pick in the same scene: the beautician answers right where the guest stands.
    window.addEventListener('beautician:ask', (event) => sendMessage(event.detail.message));

    setBusy(true);
    boot().then(() => {
        ready = true;
        scrollToBottom();
    }).catch(() => {
        statusBanner.textContent = root.dataset.labelConnectionError;
        statusBanner.classList.remove('hidden');
        messagesEl.appendChild(bubble('assistant', config.labels.intro));
    }).finally(() => {
        setBusy(false);

        // The guest chose this topic from the menu on the previous scene:
        // let the new scene settle for a beat, then carry on the conversation.
        const topic = window.takePendingBeauticianTopic?.();
        if (topic && ready) window.setTimeout(() => sendMessage(topic), 650);
    });
}

document.addEventListener('DOMContentLoaded', initBeautician);

function initDetailGallery() {
    document.querySelectorAll('[data-detail-gallery]').forEach((gallery) => {
        const main = gallery.querySelector('[data-detail-gallery-main]');
        const thumbs = [...gallery.querySelectorAll('[data-detail-thumb]')];
        if (!main) return;

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                main.src = thumb.dataset.src;
                main.alt = thumb.dataset.alt || '';
                thumbs.forEach((other) => other.toggleAttribute('aria-current', other === thumb));
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', initDetailGallery);
