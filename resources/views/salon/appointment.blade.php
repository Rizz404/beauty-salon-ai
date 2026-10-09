@php
    $money = fn ($value) => $salon->currency.' '.number_format((float) $value, 0, ',', '.');
    $duration = fn ($minutes) => \App\Modules\Experience\Presenters\SceneNarrations::duration($minutes, $locale);
@endphp
<section class="stage-content stage-panel-right @container" aria-label="{{ $wizard['title'] }}">
    <a href="{{ route('salon.show', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit data-tour-line="{{ $narration['tour_home'] }}" class="panel-close" aria-label="{{ $stageCopy['back'] }}"><span aria-hidden="true">×</span></a>
    <p class="stage-eyebrow">{{ $salon->name }}</p>
    <h2>{{ $wizard['title'] }}</h2>

    <div class="wizard" data-wizard
        data-slots-url="{{ route('appointment.slots', $salon->slug) }}"
        data-quote-url="{{ route('appointment.quote', $salon->slug) }}"
        data-submit-url="{{ route('appointment.store', $salon->slug) }}"
        data-salon-slug="{{ $salon->slug }}"
        data-locale="{{ $locale }}"
        data-currency="{{ $salon->currency }}"
        data-today="{{ $today }}"
        data-max-days="{{ $maxAdvanceDays }}"
        data-preselect-service="{{ $preselectedService ?? '' }}"
        data-preselect-stylist="{{ $preselectedStylist ?? '' }}"
    >
        <div data-wizard-flow>
            <p class="mt-3 max-w-xl text-sm leading-relaxed text-stone-600">{{ $wizard['intro'] }}</p>

            <div class="wizard-progress" aria-label="{{ $wizard['title'] }}">
                <p class="wizard-step-label" data-wizard-step-label aria-live="polite"></p>
                <ol>
                    @foreach ($wizard['steps'] as $stepLabel)
                        <li data-progress-step><span>{{ $stepLabel }}</span></li>
                    @endforeach
                </ol>
            </div>

            <form data-wizard-form novalidate autocomplete="on">
                <div data-step="1" class="wizard-step wizard-step-wide">
                    <fieldset>
                        <legend>{{ $wizard['choose_service'] }}</legend>
                        @foreach ($services->groupBy('category') as $category => $group)
                            <p class="wizard-group">{{ $terms['category'][$category] ?? Str::headline($category) }}</p>
                            <div class="wizard-seats">
                                @foreach ($group as $item)
                                    <label class="wizard-seat" data-service-option
                                        data-name="{{ $item->translatedName($locale) }}"
                                        data-category="{{ $item->category }}"
                                        data-duration="{{ $item->duration_minutes }}"
                                        data-price="{{ (float) $item->price }}"
                                        data-deposit="{{ (float) $item->deposit }}"
                                    >
                                        <input type="radio" name="service_slug" value="{{ $item->slug }}" @disabled($item->is_unavailable)>
                                        <span class="wizard-seat-body">
                                            <strong>{{ $item->translatedName($locale) }}</strong>
                                            <span>{{ $duration($item->duration_minutes) }}</span>
                                            <span class="wizard-seat-price">{{ $money($item->price) }}@if ($item->is_unavailable) · {{ $labels['unavailable'] }}@endif</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </fieldset>
                </div>

                <div data-step="2" class="wizard-step wizard-step-wide" hidden>
                    <fieldset>
                        <legend>{{ $wizard['choose_stylist'] }}</legend>
                        <p class="wizard-hint">{{ $wizard['stylist_hint'] }}</p>
                        <div class="wizard-seats">
                            @foreach ($stylists as $item)
                                <label class="wizard-seat" data-stylist-option
                                    data-name="{{ $item->translatedName($locale) }}"
                                    data-specialty="{{ $item->specialty }}"
                                >
                                    <input type="radio" name="stylist_slug" value="{{ $item->slug }}">
                                    <span class="wizard-seat-body">
                                        <strong>{{ $item->translatedName($locale) }}</strong>
                                        <span>{{ $terms['specialty'][$item->specialty] ?? Str::headline($item->specialty) }}</span>
                                        <span>{{ $item->years_experience }} {{ $stageCopy['years_unit'] }}</span>
                                        <span class="wizard-seat-hint" data-stylist-hint></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div data-step="3" class="wizard-step" hidden>
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['date'] }}</span>
                        <input type="date" name="appointment_date" min="{{ $today }}" max="{{ now($salon->timezone)->addDays($maxAdvanceDays)->toDateString() }}" required>
                    </label>
                    <fieldset class="wizard-field-wide">
                        <legend>{{ $wizard['time'] }}</legend>
                        <p class="wizard-hint" data-slots-status>{{ $wizard['pick_date'] }}</p>
                        <div class="wizard-slots" data-slots></div>
                    </fieldset>
                    <p class="wizard-hint">{{ $wizard['slot_hint'] }}</p>
                </div>

                <div data-step="4" class="wizard-step" hidden>
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['name'] }}</span>
                        <input type="text" name="guest_name" maxlength="100" autocomplete="name" required>
                    </label>
                    <fieldset class="wizard-field-wide">
                        <legend>{{ $wizard['contact_method'] }}</legend>
                        <div class="wizard-pills">
                            @foreach (['whatsapp', 'phone', 'email'] as $method)
                                <label><input type="radio" name="contact_type" value="{{ $method }}" @checked($method === 'whatsapp')><span>{{ $wizard[$method] }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['contact_value'] }}</span>
                        <input type="text" name="contact_value" maxlength="120" autocomplete="tel" required>
                    </label>
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['occasion'] }}</span>
                        <select name="occasion" class="wizard-select">
                            <option value="">{{ $wizard['occasion_none'] }}</option>
                            @foreach ($terms['occasion'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="wizard-field wizard-field-wide">
                        <span>{{ $wizard['special'] }}</span>
                        <textarea name="special_request" rows="2" maxlength="500" placeholder="{{ $wizard['special_placeholder'] }}"></textarea>
                    </label>
                </div>

                <div data-step="5" class="wizard-step wizard-step-wide" hidden>
                    <dl class="wizard-summary" data-summary></dl>
                    <p class="wizard-total"><span>{{ $wizard['price_label'] }}</span> <strong data-summary-price></strong></p>
                    <p class="wizard-hint" data-summary-deposit></p>
                    <p class="wizard-hint">{{ $wizard['disclaimer'] }}</p>
                </div>

                <div class="wizard-error" data-wizard-error role="alert" hidden>
                    <p data-wizard-error-text></p>
                    <div class="wizard-alternatives" data-wizard-alternatives hidden>
                        <p>{{ $wizard['available_instead'] }}</p>
                        <ul></ul>
                    </div>
                </div>

                <div class="wizard-actions">
                    <button type="button" class="wizard-secondary" data-wizard-back hidden>{{ $wizard['back'] }}</button>
                    <button type="button" class="stage-action" data-wizard-next>{{ $wizard['next'] }} <span aria-hidden="true">→</span></button>
                    <button type="submit" class="stage-action" data-wizard-submit hidden>{{ $wizard['submit'] }}</button>
                </div>
            </form>
        </div>

        <div class="wizard-done" data-wizard-done hidden>
            <p class="stage-eyebrow">{{ $wizard['done_title'] }}</p>
            <p class="wizard-reference-label">{{ $wizard['reference'] }}</p>
            <p class="wizard-reference" data-done-reference></p>
            <p class="wizard-status">{{ $wizard['awaiting'] }}</p>
            <p class="mt-4 max-w-md text-sm leading-relaxed text-stone-600">{{ $wizard['done_hint'] }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a class="stage-action" data-done-whatsapp target="_blank" rel="noopener" hidden>{{ $wizard['send_whatsapp'] }} <span aria-hidden="true">↗</span></a>
                <a class="wizard-secondary" data-done-phone hidden>{{ $wizard['call_salon'] }}</a>
                <a class="wizard-secondary" data-done-email hidden>{{ $wizard['email_salon'] }}</a>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('salon.show', ['salonSlug' => $salon->slug, 'lang' => $locale]) }}" data-stage-exit class="wizard-secondary">{{ $stageCopy['back'] }}</a>
                <button type="button" class="wizard-secondary" data-wizard-reset>{{ $wizard['new_request'] }}</button>
            </div>
        </div>

        <script type="application/json" data-wizard-labels>@json($wizard)</script>
    </div>
</section>
