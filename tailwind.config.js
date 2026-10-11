import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * MenkyoNavi Japanese traditional palette.
 * Semantic aliases (navy/accent/surface) keep existing Blade classes working.
 */
const palette = {
    aizome: '#26466D',
    deepIndigo: '#172D46',
    washi: '#F7F3E9',
    softWhite: '#FFFEFA',
    vermilion: '#C6533E',
    matcha: '#71865A',
    gold: '#B88A44',
    sumi: '#252A2D',
    muted: '#70777C',
    border: '#E5DFD3',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/livewire/livewire/src/Features/SupportPagination/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                navy: {
                    DEFAULT: palette.deepIndigo,
                    soft: palette.aizome,
                    muted: palette.muted,
                },
                accent: {
                    DEFAULT: palette.aizome,
                    dark: palette.deepIndigo,
                    bright: '#3A5F8A',
                },
                vermilion: {
                    DEFAULT: palette.vermilion,
                    soft: '#F5E0DC',
                    bright: '#D46550',
                    tint: '#F8E9E5',
                },
                teal: {
                    DEFAULT: palette.matcha,
                    soft: '#E8EDE3',
                    bright: '#84986B',
                },
                amber: {
                    DEFAULT: palette.gold,
                    soft: '#F5EDD9',
                    bright: '#C99A52',
                },
                matcha: {
                    DEFAULT: palette.matcha,
                    soft: '#E8EDE3',
                    bright: '#84986B',
                    tint: '#EAF0E4',
                },
                gold: {
                    DEFAULT: palette.gold,
                    soft: '#F5EDD9',
                    bright: '#C99A52',
                    tint: '#F6EEDC',
                },
                ink: palette.sumi,
                paper: palette.softWhite,
                washi: palette.washi,
                surface: palette.washi,
                border: palette.border,
                success: palette.matcha,
                danger: palette.vermilion,
            },
            fontFamily: {
                sans: [
                    '"Hiragino Sans"',
                    '"Hiragino Kaku Gothic ProN"',
                    '"Yu Gothic UI"',
                    '"Yu Gothic"',
                    '"Noto Sans JP"',
                    'Meiryo',
                    '"Noto Sans SC"',
                    '"Malgun Gothic"',
                    '"Segoe UI"',
                    'system-ui',
                    ...defaultTheme.fontFamily.sans,
                ],
            },
            fontSize: {
                'display': ['2.75rem', { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '700' }],
            },
            boxShadow: {
                soft: '0 4px 18px rgba(23, 45, 70, 0.06)',
                card: '0 10px 30px rgba(23, 45, 70, 0.09)',
                glow: '0 10px 28px rgba(38, 70, 109, 0.16)',
                lift: '0 14px 34px rgba(23, 45, 70, 0.12)',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            maxWidth: {
                content: '72rem',
            },
            transitionTimingFunction: {
                menkyo: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            keyframes: {
                'hero-fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(14px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'accent-draw': {
                    '0%': { transform: 'scaleX(0)' },
                    '100%': { transform: 'scaleX(1)' },
                },
                'float-soft': {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-6px)' },
                },
                'road-reveal': {
                    '0%': { opacity: '0', transform: 'translateY(18px) scale(0.98)' },
                    '100%': { opacity: '1', transform: 'translateY(0) scale(1)' },
                },
            },
            animation: {
                'hero-fade-up': 'hero-fade-up 0.55s cubic-bezier(0.22, 1, 0.36, 1) both',
                'accent-draw': 'accent-draw 0.6s cubic-bezier(0.22, 1, 0.36, 1) both',
                'float-soft': 'float-soft 5.5s ease-in-out infinite',
                'road-reveal': 'road-reveal 0.65s cubic-bezier(0.22, 1, 0.36, 1) both',
            },
        },
    },

    plugins: [forms],
};
