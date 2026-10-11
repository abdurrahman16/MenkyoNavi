@php
    $year = now()->year;
@endphp

<footer class="border-t border-border bg-white">
    <div class="section-shell py-12 sm:py-14">
        <div class="grid gap-10 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <a href="{{ route('home') }}" class="inline-block rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                    <x-logo variant="full" class="h-10 w-auto max-w-[14rem] sm:h-11" />
                </a>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-navy-muted">
                    {{ __('navigation.footer_blurb') }}
                </p>
                <p class="mt-4 text-xs leading-relaxed text-navy-muted">
                    {{ __('navigation.independent_disclaimer') }}
                </p>
                @php
                    $location = app()->getLocale() === 'ja'
                        ? config('menkyo.operating_location.ja')
                        : config('menkyo.operating_location.en');
                @endphp
                <p class="mt-3 text-xs leading-relaxed text-navy-muted">
                    {{ __('pages.location_label') }}: {{ $location }}
                    <br>{{ __('pages.contact_label') }}: <x-ui.contact-link class="text-accent hover:text-accent-dark" />
                </p>
            </div>

            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4 sm:gap-8 lg:col-span-8">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-navy">{{ __('navigation.learn') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('learn.index') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.all_topics') }}</a></li>
                        <li><a href="{{ route('learn.road-signs') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.road_signs') }}</a></li>
                        <li><a href="{{ route('learn.traffic-rules') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.traffic_rules') }}</a></li>
                        <li><a href="{{ route('learn.hazard-prediction') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.hazard_prediction') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-navy">{{ __('navigation.exams') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('exams.index') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.exam_hub') }}</a></li>
                        <li><a href="{{ route('exams.gaimen-kirikae') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.gaimen_kirikae') }}</a></li>
                        <li><a href="{{ route('exams.karimen') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.karimen') }}</a></li>
                        <li><a href="{{ route('exams.honmen') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.honmen') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-navy">{{ __('navigation.guides') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('guides.index') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.all_guides') }}</a></li>
                        <li><a href="{{ route('guides.foreign-license-conversion') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.foreign_license_conversion') }}</a></li>
                        <li><a href="{{ route('guides.required-documents') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.required_documents') }}</a></li>
                        <li><a href="{{ route('guides.exam-centers') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.exam_centers') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-navy">{{ __('navigation.legal_info') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('privacy-policy') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.privacy_policy') }}</a></li>
                        <li><a href="{{ route('terms-of-service') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.terms_of_service') }}</a></li>
                        <li><a href="{{ route('disclaimer') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.disclaimer') }}</a></li>
                        <li><a href="{{ route('cookie-policy') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.cookie_policy') }}</a></li>
                        <li><a href="{{ route('advertising-disclosure') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.advertising_disclosure') }}</a></li>
                        <li><a href="{{ route('editorial-policy') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.editorial_policy') }}</a></li>
                        <li><a href="{{ route('sources') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.sources') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.contact') }}</a></li>
                        <li><a href="{{ route('about') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.about') }}</a></li>
                        <li><a href="{{ route('faq') }}" class="text-navy-muted hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ __('navigation.faq') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-2 border-t border-border pt-8 text-sm text-navy-muted sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ $year }} MenkyoNavi. {{ __('navigation.copyright') }}</p>
            <p class="text-xs">{{ __('navigation.verify_requirements') }}</p>
        </div>
    </div>
</footer>
