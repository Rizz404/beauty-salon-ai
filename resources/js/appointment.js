/**
 * Guided appointment wizard: five short steps in its own scene — treatment,
 * stylist, date and start time, contact, summary — then a reference number
 * and WhatsApp / phone / email hand-over. Progress survives leaving the page
 * and switching language (sessionStorage), and every rule is enforced again
 * on the server, which is the only source of availability: the start times on
 * offer are fetched for the chosen treatment, stylist and day, so each one
 * already fits the full length of the treatment.
 */
import { postJson } from './shared/api.js';

function initAppointmentWizard() {
    const root = document.querySelector('[data-wizard]');
    if (!root) return;

    const labels = JSON.parse(root.querySelector('[data-wizard-labels]').textContent);
    const form = root.querySelector('[data-wizard-form]');
    const flow = root.querySelector('[data-wizard-flow]');
    const done = root.querySelector('[data-wizard-done]');
    const steps = [...root.querySelectorAll('[data-step]')];
    const progress = [...root.querySelectorAll('[data-progress-step]')];
    const stepLabel = root.querySelector('[data-wizard-step-label]');
    const backButton = root.querySelector('[data-wizard-back]');
    const nextButton = root.querySelector('[data-wizard-next]');
    const submitButton = root.querySelector('[data-wizard-submit]');
    const errorBox = root.querySelector('[data-wizard-error]');
    const errorText = root.querySelector('[data-wizard-error-text]');
    const alternativesBox = root.querySelector('[data-wizard-alternatives]');
    const serviceOptions = [...root.querySelectorAll('[data-service-option]')];
    const stylistOptions = [...root.querySelectorAll('[data-stylist-option]')];
    const slotsBox = root.querySelector('[data-slots]');
    const slotsStatus = root.querySelector('[data-slots-status]');

    const config = {
        slotsUrl: root.dataset.slotsUrl,
        quoteUrl: root.dataset.quoteUrl,
        submitUrl: root.dataset.submitUrl,
        locale: root.dataset.locale,
        currency: root.dataset.currency,
        today: root.dataset.today,
        maxDays: Number(root.dataset.maxDays),
        draftKey: `appointment_draft_${root.dataset.salonSlug}`,
        tokenKey: `beautician_token_${root.dataset.salonSlug}`,
    };

    const fieldStep = {
        service_slug: 1, stylist_slug: 2, appointment_date: 3, time_slot: 3,
        guest_name: 4, contact_type: 4, contact_value: 4, occasion: 4, special_request: 4,
    };

    let current = 1;
    let quote = null;
    let busy = false;
    let pendingSlot = '';
    let slotsRequest = 0;
    let slotRanges = {};

    const numberLocale = config.locale === 'ja' ? 'ja-JP' : config.locale === 'en' ? 'en-US' : 'id-ID';
    const text = (template, values) => Object.entries(values).reduce((out, [key, value]) => out.replace(`:${key}`, value), template);
    const money = (value) => `${config.currency} ${new Intl.NumberFormat(numberLocale, { maximumFractionDigits: 0 }).format(Number(value) || 0)}`;
    const depositText = (value) => (Number(value) > 0 ? money(value) : labels.deposit_free);

    function formatDate(iso) {
        const [year, month, day] = iso.split('-').map(Number);
        return new Intl.DateTimeFormat(config.locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(year, month - 1, day));
    }

    function daysFromToday(iso) {
        const [y1, m1, d1] = config.today.split('-').map(Number);
        const [y2, m2, d2] = iso.split('-').map(Number);
        return Math.round((Date.UTC(y2, m2 - 1, d2) - Date.UTC(y1, m1 - 1, d1)) / 86400000);
    }

    function values() {
        const data = new FormData(form);
        return {
            service_slug: data.get('service_slug') || '',
            stylist_slug: data.get('stylist_slug') || '',
            appointment_date: data.get('appointment_date') || '',
            time_slot: data.get('time_slot') || '',
            occasion: data.get('occasion') || '',
            guest_name: (data.get('guest_name') || '').trim(),
            contact_type: data.get('contact_type') || 'whatsapp',
            contact_value: (data.get('contact_value') || '').trim(),
            special_request: (data.get('special_request') || '').trim(),
        };
    }

    function saveDraft() {
        try {
            sessionStorage.setItem(config.draftKey, JSON.stringify({ step: current, values: values() }));
        } catch { /* storage unavailable: the wizard still works without it */ }
    }

    function clearDraft() {
        try {
            sessionStorage.removeItem(config.draftKey);
        } catch { /* nothing to clear */ }
    }

    function restoreDraft() {
        try {
            const draft = JSON.parse(sessionStorage.getItem(config.draftKey) || 'null');
            if (!draft) return false;
            const { values: saved } = draft;
            ['appointment_date', 'occasion', 'guest_name', 'contact_value', 'special_request'].forEach((name) => {
                if (saved[name]) form.elements[name].value = saved[name];
            });
            if (saved.contact_type) form.elements.contact_type.value = saved.contact_type;
            if (saved.service_slug) form.elements.service_slug.value = saved.service_slug;
            if (saved.stylist_slug) form.elements.stylist_slug.value = saved.stylist_slug;
            pendingSlot = saved.time_slot || '';
            current = Math.min(Math.max(Number(draft.step) || 1, 1), steps.length);
            return true;
        } catch { /* ignore a corrupt draft */ }

        return false;
    }

    function clearError() {
        errorBox.hidden = true;
        errorText.textContent = '';
        alternativesBox.hidden = true;
        alternativesBox.querySelector('ul').replaceChildren();
    }

    function showError(message, alternatives = []) {
        errorText.textContent = message;
        errorBox.hidden = false;

        const list = alternativesBox.querySelector('ul');
        list.replaceChildren();
        alternatives.forEach((alternative) => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'wizard-secondary';
            button.textContent = alternative.name;
            button.addEventListener('click', () => {
                if (alternative.type === 'stylist') {
                    form.elements.stylist_slug.value = alternative.slug;
                    refreshStylists();
                    loadSlots();
                } else {
                    pendingSlot = alternative.time_slot;
                    chooseSlot(alternative.time_slot);
                }
                clearError();
                saveDraft();
            });
            item.appendChild(button);
            list.appendChild(item);
        });
        alternativesBox.hidden = alternatives.length === 0;
    }

    /** The start times are radios added at runtime, so pick one by value instead of form.elements. */
    function chooseSlot(value) {
        form.querySelectorAll('input[name="time_slot"]').forEach((radio) => {
            radio.checked = value !== '' && radio.value === value;
        });
    }

    function selectedService() {
        const slug = form.elements.service_slug.value;
        return serviceOptions.find((option) => option.querySelector('input').value === slug) || null;
    }

    function selectedStylist() {
        const slug = form.elements.stylist_slug.value;
        return stylistOptions.find((option) => option.querySelector('input').value === slug) || null;
    }

    /** Only the stylists whose specialty covers the chosen treatment can be picked. */
    function refreshStylists() {
        const service = selectedService();

        stylistOptions.forEach((option) => {
            const input = option.querySelector('input');
            const suitable = !service || option.dataset.specialty === service.dataset.category;
            option.hidden = !suitable;
            input.disabled = !suitable;
            option.querySelector('[data-stylist-hint]').textContent = '';
            if (!suitable && input.checked) input.checked = false;
        });
    }

    function setSlotsMessage(message) {
        slotsStatus.textContent = message;
        slotsStatus.hidden = !message;
    }

    function renderSlots(slots) {
        slotsBox.replaceChildren();
        slotRanges = {};

        slots.forEach((slot) => {
            slotRanges[slot.time_slot] = slot.time_range;
            const label = document.createElement('label');
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'time_slot';
            input.value = slot.time_slot;
            const span = document.createElement('span');
            span.textContent = slot.time_range;
            label.append(input, span);
            slotsBox.appendChild(label);
        });

        if (pendingSlot && slotRanges[pendingSlot]) chooseSlot(pendingSlot);
        else pendingSlot = '';
    }

    /** Asks the server which start times still fit the whole treatment that day. */
    async function loadSlots() {
        const data = values();
        slotsBox.replaceChildren();
        slotRanges = {};

        if (!data.service_slug || !data.stylist_slug || !data.appointment_date) {
            setSlotsMessage(labels.pick_date);
            return;
        }

        const request = ++slotsRequest;
        setSlotsMessage(labels.loading_slots);

        try {
            const result = await postJson(config.slotsUrl, {
                service_slug: data.service_slug,
                stylist_slug: data.stylist_slug,
                appointment_date: data.appointment_date,
                locale: config.locale,
            });
            if (request !== slotsRequest) return;

            if (!result.ok) {
                setSlotsMessage('');
                handleFailure(result);
                return;
            }

            renderSlots(result.body.slots);
            setSlotsMessage(result.body.slots.length ? '' : labels.no_slots);
            saveDraft();
        } catch {
            if (request === slotsRequest) setSlotsMessage(labels.error_network);
        }
    }

    function validate(step) {
        const data = values();

        if (step === 1) {
            if (!selectedService()) return { field: 'service_slug', message: labels.service_unknown };
        }

        if (step === 2) {
            const service = selectedService();
            const stylist = selectedStylist();
            if (!stylist) return { field: 'stylist_slug', message: labels.stylist_unknown };
            if (service && stylist.dataset.specialty !== service.dataset.category) return { field: 'stylist_slug', message: labels.stylist_mismatch };
        }

        if (step === 3) {
            if (!data.appointment_date || data.appointment_date < config.today) return { field: 'appointment_date', message: labels.date_past };
            if (daysFromToday(data.appointment_date) > config.maxDays) return { field: 'appointment_date', message: text(labels.date_far, { max: config.maxDays }) };
            if (!data.time_slot) return { field: 'time_slot', message: labels.slot_unknown };
        }

        if (step === 4) {
            if (!data.guest_name) return { field: 'guest_name', message: labels.invalid };
            const valid = data.contact_type === 'email'
                ? /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.contact_value)
                : /^\+?[0-9\s\-().]{6,20}$/.test(data.contact_value);
            if (!valid) return { field: 'contact_value', message: data.contact_type === 'email' ? labels.contact_email : labels.contact_phone };
        }

        return null;
    }

    function setBusy(value, label = null) {
        busy = value;
        [backButton, nextButton, submitButton].forEach((button) => { button.disabled = value; });
        if (label) nextButton.firstChild.textContent = `${label} `;
        else nextButton.firstChild.textContent = `${labels.next} `;
    }

    function showStep(step, focus = false) {
        current = step;
        steps.forEach((element, index) => { element.hidden = index + 1 !== step; });
        progress.forEach((element, index) => {
            element.classList.toggle('is-current', index + 1 === step);
            element.classList.toggle('is-done', index + 1 < step);
            if (index + 1 === step) element.setAttribute('aria-current', 'step');
            else element.removeAttribute('aria-current');
        });
        stepLabel.textContent = `${text(labels.step_of, { current: step, total: steps.length })} · ${labels.steps[step - 1]}`;
        backButton.hidden = step === 1;
        nextButton.hidden = step === steps.length;
        submitButton.hidden = step !== steps.length;

        if (step === 2) refreshStylists();
        if (step === 3) loadSlots();
        if (step === steps.length) renderSummary();
        if (focus) steps[step - 1].querySelector('input:not([disabled]), textarea, select')?.focus({ preventScroll: true });
        saveDraft();
    }

    function goToField(field) {
        showStep(fieldStep[field] || current, true);
    }

    function appointmentPayload() {
        const { service_slug, stylist_slug, appointment_date, time_slot, occasion } = values();
        return { service_slug, stylist_slug, appointment_date, time_slot, occasion: occasion || null, locale: config.locale };
    }

    function handleFailure(result) {
        if (result.status === 422 && result.body.errors) {
            const [field, messages] = Object.entries(result.body.errors)[0];
            showError(messages[0], result.body.alternatives || []);
            if (fieldStep[field] && fieldStep[field] !== current) showStep(fieldStep[field]);
            return;
        }
        showError(labels.error_generic);
    }

    async function next() {
        if (busy || current >= steps.length) return;
        clearError();

        const problem = validate(current);
        if (problem) {
            showError(problem.message);
            form.elements[problem.field]?.focus?.();
            return;
        }

        if (current === 3) {
            setBusy(true, labels.checking);
            try {
                const result = await postJson(config.quoteUrl, appointmentPayload());
                if (!result.ok) {
                    handleFailure(result);
                    return;
                }
                quote = result.body;
            } catch {
                showError(labels.error_network);
                return;
            } finally {
                setBusy(false);
            }
        }

        showStep(current + 1, true);
    }

    function summaryRow(label, value, step) {
        const wrapper = document.createElement('div');
        const term = document.createElement('dt');
        term.textContent = label;
        const detail = document.createElement('dd');
        const span = document.createElement('span');
        span.textContent = value;
        const edit = document.createElement('button');
        edit.type = 'button';
        edit.textContent = labels.edit;
        edit.addEventListener('click', () => showStep(step, true));
        detail.append(span, edit);
        wrapper.append(term, detail);
        return wrapper;
    }

    function renderSummary() {
        const data = values();
        const service = selectedService();
        const stylist = selectedStylist();
        const occasionLabel = data.occasion ? form.querySelector(`select[name="occasion"] option[value="${data.occasion}"]`)?.textContent : '';
        const range = quote?.time_range || slotRanges[data.time_slot] || data.time_slot;

        const rows = [
            summaryRow(labels.service, service ? `${service.dataset.name} · ${text(labels.minutes, { count: service.dataset.duration })}` : '', 1),
            summaryRow(labels.stylist, stylist ? stylist.dataset.name : '', 2),
            summaryRow(labels.dateTime, `${formatDate(data.appointment_date)} · ${range}`, 3),
            summaryRow(labels.contact, `${data.guest_name} · ${labels[data.contact_type]}: ${data.contact_value}`, 4),
        ];
        if (occasionLabel) rows.push(summaryRow(labels.occasion, occasionLabel, 4));
        if (data.special_request) rows.push(summaryRow(labels.special, data.special_request, 4));

        root.querySelector('[data-summary]').replaceChildren(...rows);
        root.querySelector('[data-summary-price]').textContent = quote ? money(quote.price) : '';
        root.querySelector('[data-summary-deposit]').textContent = quote ? `${labels.deposit_label}: ${depositText(quote.deposit)}` : '';
    }

    function showDone(result) {
        flow.hidden = true;
        done.hidden = false;
        root.querySelector('[data-done-reference]').textContent = result.reference;

        [['whatsapp', 'whatsapp_url'], ['phone', 'phone_url'], ['email', 'email_url']].forEach(([name, key]) => {
            const link = root.querySelector(`[data-done-${name}]`);
            const url = result.handover?.[key];
            link.hidden = !url;
            if (url) link.href = url;
        });
    }

    async function submit(event) {
        event.preventDefault();
        if (busy || current !== steps.length) return;
        clearError();

        const problem = [1, 2, 3, 4].map(validate).find(Boolean);
        if (problem) {
            showError(problem.message);
            goToField(problem.field);
            return;
        }

        setBusy(true);
        submitButton.textContent = labels.sending;

        try {
            const result = await postJson(config.submitUrl, {
                ...appointmentPayload(),
                guest_name: values().guest_name,
                contact_type: values().contact_type,
                contact_value: values().contact_value,
                special_request: values().special_request,
                guest_token: localStorage.getItem(config.tokenKey) || undefined,
            });

            if (!result.ok) {
                handleFailure(result);
                return;
            }

            clearDraft();
            showDone(result.body);
            window.salonSound?.play('thanks');
        } catch {
            showError(labels.error_network);
        } finally {
            setBusy(false);
            submitButton.textContent = labels.submit;
        }
    }

    function reset() {
        form.reset();
        quote = null;
        pendingSlot = '';
        slotsBox.replaceChildren();
        setSlotsMessage(labels.pick_date);
        clearError();
        done.hidden = true;
        flow.hidden = false;
        clearDraft();
        refreshStylists();
        showStep(1, true);
    }

    /** A treatment or stylist page sent the guest here: start further along. */
    function preselect(serviceSlug, stylistSlug) {
        if (flow.hidden) return;

        const service = serviceOptions.find((option) => option.querySelector('input').value === serviceSlug);
        if (service && !service.querySelector('input').disabled) {
            form.elements.service_slug.value = serviceSlug;
            refreshStylists();
        }

        const stylist = stylistOptions.find((option) => option.querySelector('input').value === stylistSlug);
        if (stylist && !stylist.querySelector('input').disabled) {
            form.elements.stylist_slug.value = stylistSlug;
        }

        if (selectedService() && selectedStylist()) showStep(Math.max(current, 3));
        else if (selectedService()) showStep(Math.max(current, 2));
    }

    form.addEventListener('input', () => saveDraft());
    form.addEventListener('change', (event) => {
        const name = event.target.name;

        if (name === 'service_slug') refreshStylists();

        if (name === 'service_slug' || name === 'stylist_slug' || name === 'appointment_date') {
            chooseSlot('');
            pendingSlot = '';
            if (current === 3) loadSlots();
        }

        saveDraft();
    });
    form.addEventListener('submit', submit);
    nextButton.addEventListener('click', next);
    backButton.addEventListener('click', () => { clearError(); showStep(Math.max(1, current - 1), true); });
    root.querySelector('[data-wizard-reset]').addEventListener('click', reset);
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA' && current < steps.length) {
            event.preventDefault();
            next();
        }
    });

    restoreDraft();
    refreshStylists();
    setSlotsMessage(labels.pick_date);
    showStep(current);
    preselect(root.dataset.preselectService || '', root.dataset.preselectStylist || '');
}

document.addEventListener('DOMContentLoaded', initAppointmentWizard);
