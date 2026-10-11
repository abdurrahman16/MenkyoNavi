@php
    /** @var bool $externalMail */
    /** @var string $mailer */
    /** @var string $maskedEmail */
    $status = session('status');
@endphp

<x-guest-layout>
    <div class="space-y-5" data-email-verification-notice>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-accent">{{ __('auth.verify_eyebrow') }}</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-navy">{{ __('auth.verify_title') }}</h1>
            <p class="mt-3 text-sm leading-relaxed text-navy/80 sm:text-base">
                {{ __('auth.verify_lead') }}
            </p>
        </div>

        <div class="rounded-2xl border border-border bg-washi/70 px-4 py-3 text-sm leading-relaxed text-navy/80">
            <p>{{ __('auth.verify_sent_to', ['email' => $maskedEmail]) }}</p>
            @if ($externalMail)
                <p class="mt-2">{{ __('auth.verify_check_inbox') }}</p>
            @else
                <p class="mt-2" data-mail-log-notice>{{ __('auth.verify_log_mailer_notice') }}</p>
            @endif
        </div>

        @if ($status === 'registered')
            <div class="rounded-2xl border border-matcha/30 bg-matcha/10 px-4 py-3 text-sm font-medium text-navy" role="status">
                {{ __('auth.verify_registered_status') }}
            </div>
        @endif

        @if ($status === 'verification-link-sent')
            <div class="rounded-2xl border border-matcha/30 bg-matcha/10 px-4 py-3 text-sm font-medium text-navy" role="status">
                {{ __('auth.verify_resent_external') }}
            </div>
        @endif

        @if ($status === 'verification-link-logged')
            <div class="rounded-2xl border border-gold/35 bg-gold/10 px-4 py-3 text-sm font-medium text-navy" role="status" data-mail-log-status>
                {{ __('auth.verify_resent_logged') }}
            </div>
        @endif

        @if ($status === 'verification-send-failed')
            <div class="rounded-2xl border border-vermilion/30 bg-vermilion/10 px-4 py-3 text-sm font-medium text-navy" role="alert">
                {{ __('auth.verify_send_failed') }}
            </div>
        @endif

        @if ($status === 'verification-link-invalid')
            <div class="rounded-2xl border border-vermilion/30 bg-vermilion/10 px-4 py-3 text-sm font-medium text-navy" role="alert">
                {{ __('auth.verify_link_invalid') }}
            </div>
        @endif

        <ul class="space-y-2 text-sm leading-relaxed text-navy/80">
            <li>• {{ __('auth.verify_why') }}</li>
            <li>• {{ __('auth.verify_available_before') }}</li>
            <li>• {{ __('auth.verify_spam_hint') }}</li>
        </ul>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="btn-primary inline-flex min-h-12 w-full items-center justify-center sm:w-auto" data-resend-verification>
                    {{ __('auth.verify_resend') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="btn-secondary inline-flex min-h-12 w-full items-center justify-center sm:w-auto">
                    {{ __('auth.verify_logout') }}
                </button>
            </form>
        </div>

        <div class="flex flex-wrap gap-x-4 gap-y-2 border-t border-border pt-4 text-sm">
            <a href="{{ route('home') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('auth.verify_home') }}</a>
            <a href="{{ route('learn.index') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('auth.verify_learn') }}</a>
            <a href="{{ route('contact') }}" class="font-semibold text-accent hover:text-accent-dark">{{ __('auth.verify_contact') }}</a>
        </div>
    </div>
</x-guest-layout>
