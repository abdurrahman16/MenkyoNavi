@props([
    'class' => 'font-semibold text-accent hover:text-accent-dark',
])

<a
    href="{{ route('contact') }}"
    {{ $attributes->merge(['class' => $class]) }}
>{{ __('pages.contact.breadcrumb') }}</a>
