/**
 * MenkyoNavi cinematic motion system.
 * Progressive enhancement only — content stays visible without JS / with reduced motion.
 * Never intercepts forms, auth, exam submission, or Livewire updates.
 */

const REDUCE_MOTION_QUERY = '(prefers-reduced-motion: reduce)';
const FINE_POINTER_QUERY = '(hover: hover) and (pointer: fine)';

let revealObserver = null;

function prefersReducedMotion() {
    return window.matchMedia(REDUCE_MOTION_QUERY).matches;
}

function hasFinePointer() {
    return window.matchMedia(FINE_POINTER_QUERY).matches;
}

function revealNode(node) {
    node.classList.add('is-revealed');

    if (node.classList.contains('motion-stagger-group')) {
        node.querySelectorAll('[data-reveal-child]').forEach((child, index) => {
            child.style.transitionDelay = `${40 + Math.min(index, 11) * 45}ms`;
            child.classList.add('is-revealed');
        });
    }
}

function revealAll(nodes) {
    nodes.forEach((node) => revealNode(node));
}

/**
 * Tall sections (e.g. a full sign library) never reach the default IO threshold
 * while opacity:0, which looks like a giant blank gap. Reveal them immediately.
 */
function isTallRevealTarget(node) {
    const viewportHeight = Math.max(window.innerHeight || 0, 1);
    const height = node.getBoundingClientRect().height;

    return height >= viewportHeight * 0.7;
}

function unboundRevealNodes() {
    return Array.from(
        document.querySelectorAll(
            '[data-reveal], .reveal, .motion-reveal-up, .motion-reveal-left, .motion-reveal-right, .motion-reveal-scale, .motion-reveal-mask, .motion-stagger-group, .journey-track',
        ),
    ).filter((node) => node.dataset.revealBound !== '1');
}

function initScrollReveal() {
    document.documentElement.classList.add('js-motion');

    if (prefersReducedMotion()) {
        document.documentElement.classList.add('reduce-motion');
    }

    const nodes = unboundRevealNodes();

    if (nodes.length === 0) {
        return;
    }

    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
        revealAll(nodes);
        nodes.forEach((node) => {
            node.dataset.revealBound = '1';
        });

        return;
    }

    if (revealObserver === null) {
        revealObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    revealNode(entry.target);
                    revealObserver.unobserve(entry.target);
                });
            },
            {
                rootMargin: '0px 0px -4% 0px',
                threshold: [0, 0.01, 0.08],
            },
        );
    }

    nodes.forEach((node) => {
        node.dataset.revealBound = '1';

        if (isTallRevealTarget(node)) {
            revealNode(node);

            return;
        }

        revealObserver.observe(node);
    });
}

function initHeroVisual() {
    const visuals = document.querySelectorAll('[data-hero-visual]');

    visuals.forEach((visual) => {
        if (visual.dataset.heroReady === '1') {
            return;
        }

        visual.dataset.heroReady = '1';

        // Allow CSS entrance styles to apply before flipping to ready.
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                visual.classList.add('is-ready');
            });
        });
    });
}

function initHeaderElevation() {
    const header = document.querySelector('[data-site-header]');

    if (!header || header.dataset.scrollBound === '1') {
        return;
    }

    header.dataset.scrollBound = '1';

    const update = () => {
        header.classList.toggle('is-elevated', window.scrollY > 8);
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
}

function initHeroParallax() {
    const layer = document.querySelector('[data-parallax-layer]');

    if (!layer || prefersReducedMotion() || !hasFinePointer()) {
        return;
    }

    if (layer.dataset.parallaxBound === '1') {
        return;
    }

    layer.dataset.parallaxBound = '1';

    let frame = 0;

    const onMove = (event) => {
        if (frame) {
            return;
        }

        frame = window.requestAnimationFrame(() => {
            const rect = layer.getBoundingClientRect();
            const x = (event.clientX - rect.left) / Math.max(rect.width, 1) - 0.5;
            const y = (event.clientY - rect.top) / Math.max(rect.height, 1) - 0.5;
            document.documentElement.style.setProperty('--parallax-x', `${(x * 10).toFixed(2)}px`);
            document.documentElement.style.setProperty('--parallax-y', `${(y * 8).toFixed(2)}px`);
            frame = 0;
        });
    };

    layer.addEventListener('pointermove', onMove, { passive: true });
    layer.addEventListener(
        'pointerleave',
        () => {
            document.documentElement.style.setProperty('--parallax-x', '0px');
            document.documentElement.style.setProperty('--parallax-y', '0px');
        },
        { passive: true },
    );
}

function initResultRings() {
    document.querySelectorAll('[data-result-ring]').forEach((ring) => {
        if (ring.dataset.ringBound === '1') {
            return;
        }

        ring.dataset.ringBound = '1';
        const target = Number(ring.dataset.resultRing || 0);

        if (prefersReducedMotion()) {
            ring.style.setProperty('--result-progress', String(Math.max(0, Math.min(100, target))));

            return;
        }

        ring.style.setProperty('--result-progress', '0');
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                ring.style.setProperty('--result-progress', String(Math.max(0, Math.min(100, target))));
            });
        });
    });
}

export function initMotion() {
    initScrollReveal();
    initHeroVisual();
    initHeaderElevation();
    initHeroParallax();
    initResultRings();
}
