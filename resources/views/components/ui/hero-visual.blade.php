{{-- Cinematic SVG: road stroke-draw, car entrance, sign settle, washi depth --}}
<div
    {{ $attributes->merge(['class' => 'hero-visual-stage relative mx-auto w-full max-w-lg hero-visual-animate']) }}
    aria-hidden="true"
>
    <div class="absolute -inset-4 rounded-[2rem] bg-gradient-to-br from-accent/15 via-matcha/10 to-vermilion/10 blur-2xl"></div>
    <div class="pointer-events-none absolute right-6 top-4 h-10 w-10 rounded-full border-2 border-vermilion/40 bg-vermilion/10 float-sign" aria-hidden="true"></div>
    <div class="pointer-events-none absolute left-8 top-10 h-8 w-8 rotate-45 border-2 border-gold/50 bg-gold/15 float-sign float-sign-delay" aria-hidden="true"></div>

    <div class="hero-visual relative overflow-hidden rounded-3xl border border-border bg-paper p-4 shadow-card sm:p-5" data-hero-visual>
        <svg viewBox="0 0 420 320" class="h-auto w-full" role="img" aria-label="{{ __('pages.learn.card_signs_title') }}">
            <defs>
                <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#FFFEFA"/>
                    <stop offset="100%" stop-color="#F7F3E9"/>
                </linearGradient>
                <linearGradient id="road" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" stop-color="#252A2D"/>
                    <stop offset="100%" stop-color="#172D46"/>
                </linearGradient>
                <pattern id="seigaiha" width="28" height="14" patternUnits="userSpaceOnUse">
                    <path d="M0 14 A14 14 0 0 1 28 14" fill="none" stroke="#26466D" stroke-width="1" opacity="0.35"/>
                    <path d="M0 14 A9 9 0 0 1 18 14" fill="none" stroke="#26466D" stroke-width="1" opacity="0.28" transform="translate(5 0)"/>
                </pattern>
            </defs>

            <rect width="420" height="320" rx="24" fill="url(#sky)"/>
            <rect width="420" height="150" fill="url(#seigaiha)" opacity="0.18"/>

            {{-- Soft hills --}}
            <path d="M40 150 L120 78 L170 120 L230 62 L320 138 L380 104 L380 180 L40 180 Z" fill="#D9E3D0" opacity="0.9"/>
            <circle cx="340" cy="58" r="20" fill="#E8C9A8" opacity="0.9"/>

            {{-- Road fill --}}
            <path d="M0 220 C120 190, 300 250, 420 210 L420 320 L0 320 Z" fill="url(#road)"/>

            {{-- Animated center line (pathLength for dash draw) --}}
            <path
                class="svg-draw-road"
                pathLength="1"
                d="M40 270 C150 245, 270 285, 380 255"
                stroke="#F7F3E9"
                stroke-width="4"
                stroke-linecap="round"
                fill="none"
                opacity="0.9"
            />

            {{-- Stop-style regulatory sign (outer translate preserved; CSS motion on inner) --}}
            <g transform="translate(48,96)">
                <g class="hero-sign hero-sign-1">
                    <rect x="26" y="70" width="6" height="70" fill="#70777C"/>
                    <polygon points="29,8 50,16 58,37 50,58 29,66 8,58 0,37 8,16" fill="#C6533E" stroke="#172D46" stroke-width="2"/>
                    <text x="29" y="42" text-anchor="middle" fill="#FFFEFA" font-size="11" font-family="system-ui,sans-serif" font-weight="700">止まれ</text>
                </g>
            </g>

            {{-- Warning diamond --}}
            <g transform="translate(150,78)">
                <g class="hero-sign hero-sign-2">
                    <rect x="24" y="68" width="6" height="58" fill="#70777C"/>
                    <polygon points="27,6 50,29 27,52 4,29" fill="#B88A44" stroke="#172D46" stroke-width="2"/>
                    <circle cx="27" cy="29" r="7" fill="none" stroke="#172D46" stroke-width="2"/>
                    <path d="M27 24 v7" stroke="#172D46" stroke-width="2" stroke-linecap="round"/>
                </g>
            </g>

            {{-- Guidance plate --}}
            <g transform="translate(250,88)">
                <g class="hero-sign hero-sign-3">
                    <rect x="30" y="56" width="6" height="70" fill="#70777C"/>
                    <rect x="4" y="10" width="58" height="36" rx="4" fill="#26466D" stroke="#172D46" stroke-width="2"/>
                    <path d="M16 28 h28" stroke="#FFFEFA" stroke-width="3" stroke-linecap="round"/>
                    <path d="M36 20 l10 8 -10 8" fill="none" stroke="#FFFEFA" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
            </g>

            {{-- Car silhouette --}}
            <g transform="translate(270,205)">
                <g class="hero-car">
                    <rect x="8" y="18" width="88" height="28" rx="10" fill="#26466D"/>
                    <path d="M24 18 C32 4, 72 4, 80 18" fill="#3A5F8A"/>
                    <circle cx="28" cy="46" r="8" fill="#252A2D"/>
                    <circle cx="76" cy="46" r="8" fill="#252A2D"/>
                    <circle cx="28" cy="46" r="3.5" fill="#E5DFD3"/>
                    <circle cx="76" cy="46" r="3.5" fill="#E5DFD3"/>
                </g>
            </g>

            {{-- Learning card overlay --}}
            <g transform="translate(168,148)">
                <g class="hero-card-overlay">
                    <rect x="0" y="0" width="196" height="104" rx="14" fill="#FFFEFA" stroke="#E5DFD3" stroke-width="2"/>
                    <text x="14" y="44" fill="#172D46" font-size="20" font-family="system-ui,sans-serif" font-weight="700">一時停止</text>
                    <rect x="14" y="56" width="120" height="6" rx="3" fill="#E5DFD3"/>
                    <rect x="14" y="66" width="86" height="6" rx="3" fill="#E5DFD3"/>
                    <rect x="14" y="80" width="44" height="16" rx="8" fill="#E8EDE3"/>
                    <rect x="66" y="80" width="44" height="16" rx="8" fill="#F5E0DC"/>
                </g>
            </g>
        </svg>
    </div>
</div>
