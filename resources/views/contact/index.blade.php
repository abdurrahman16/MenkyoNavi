@php
    $operatingLocation = app()->getLocale() === 'ja'
        ? config('menkyo.operating_location.ja')
        : config('menkyo.operating_location.en');
    $fieldClasses = 'mt-2 block w-full rounded-xl border border-border bg-paper px-4 py-3 text-navy shadow-sm transition focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20';
@endphp

<x-layouts.site
    :title="__('pages.contact.meta_title')"
    :description="__('pages.contact.meta_description')"
    :canonical="url()->current()"
>
    <x-ui.breadcrumb :items="[
        ['label' => __('pages.home'), 'url' => route('home')],
        ['label' => __('pages.contact.breadcrumb'), 'url' => route('contact')],
    ]" />

    <x-ui.page-hero
        :title="__('pages.contact.title')"
        :subtitle="__('pages.contact.subtitle')"
    />

    <section class="section-pad section-washi">
        <div class="mx-auto max-w-xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 space-y-2 text-center text-sm text-navy-muted">
                <p class="text-base font-semibold text-navy">{{ config('menkyo.name') }}（{{ config('menkyo.name_ja') }}）</p>
                <p>{{ __('pages.contact.location_note', ['location' => $operatingLocation]) }}</p>
                <p>{{ __('pages.contact.instructions') }}</p>
                <p>{{ __('pages.contact.cannot_answer') }}</p>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-matcha/30 bg-matcha/10 p-4 text-sm text-navy" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-vermilion/30 bg-vermilion-soft p-4 text-sm text-navy" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('contact.store') }}"
                class="space-y-6 rounded-3xl border border-border bg-paper p-6 shadow-soft sm:p-8"
                x-data="{ submitting: false }"
                @submit="submitting = true"
            >
                @csrf

                {{-- Honeypot: leave empty --}}
                <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" name="website" id="website" value="{{ old('website') }}" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-navy">{{ __('pages.contact.name') }}</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        required
                        autocomplete="name"
                        class="{{ $fieldClasses }}"
                    />
                    @error('name')
                        <p class="mt-2 text-sm text-vermilion">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-navy">{{ __('pages.contact.email') }}</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        class="{{ $fieldClasses }}"
                    />
                    @error('email')
                        <p class="mt-2 text-sm text-vermilion">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="subject" class="block text-sm font-medium text-navy">{{ __('pages.contact.subject') }}</label>
                    <input
                        type="text"
                        name="subject"
                        id="subject"
                        value="{{ old('subject') }}"
                        required
                        class="{{ $fieldClasses }}"
                    />
                    @error('subject')
                        <p class="mt-2 text-sm text-vermilion">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-navy">{{ __('pages.contact.message') }}</label>
                    <textarea
                        name="message"
                        id="message"
                        rows="6"
                        required
                        class="{{ $fieldClasses }}"
                    >{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-2 text-sm text-vermilion">{{ $message }}</p>
                    @enderror
                </div>

                @error('website')
                    <p class="text-sm text-vermilion">{{ $message }}</p>
                @enderror

                <p class="text-xs leading-relaxed text-navy-muted">{{ __('pages.contact.form_privacy_note') }}</p>

                <button
                    type="submit"
                    class="btn-primary w-full"
                    :disabled="submitting"
                    :aria-busy="submitting.toString()"
                >
                    <span x-show="!submitting">{{ __('pages.contact.send') }}</span>
                    <span x-cloak x-show="submitting">{{ __('common.loading') }}</span>
                </button>
            </form>
        </div>
    </section>
</x-layouts.site>
