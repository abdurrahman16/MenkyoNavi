<x-layouts.exam
    title="Exam layout preview | MenkyoNavi"
    description="Local layout preview for future examination pages. Placeholder content only—no live questions or advertisements."
    :canonical="url()->current()"
    robots="noindex, nofollow"
>
    <div class="space-y-6">
        <header class="border-b border-border pb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-accent">Layout preview</p>
            <h1 class="mt-2 text-2xl font-semibold text-navy sm:text-3xl">Sample examination panel</h1>
            <p class="mt-2 text-sm text-navy-muted">
                Placeholder structure for a future timed practice session. Advertising regions stay outside this panel.
            </p>
        </header>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-surface px-4 py-3 text-sm text-navy-muted">
            <span>Question progress: 1 / 50</span>
            <span>Timer: 00:00</span>
        </div>

        <div class="space-y-3">
            <h2 class="text-lg font-semibold text-navy">Question text region</h2>
            <p class="leading-relaxed text-navy-muted">
                Japanese question text and its translation will appear here in a later phase. This preview only
                reserves readable space for the examination UI.
            </p>
            <p class="leading-relaxed text-navy-muted">
                True / False controls, navigation, and submission actions will live inside this center column so they
                remain clear of advertisement regions.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <button type="button" disabled class="btn-primary cursor-not-allowed opacity-60">True (○)</button>
            <button type="button" disabled class="btn-secondary cursor-not-allowed opacity-60">False (×)</button>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-border pt-4">
            <button type="button" disabled class="btn-secondary cursor-not-allowed opacity-60">Previous</button>
            <button type="button" disabled class="btn-secondary cursor-not-allowed opacity-60">Next</button>
            <button type="button" disabled class="btn-primary cursor-not-allowed opacity-60">Submit (preview)</button>
        </div>
    </div>
</x-layouts.exam>
