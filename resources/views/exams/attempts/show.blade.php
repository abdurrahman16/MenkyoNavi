<x-layouts.exam
    :title="$attempt->examType->name.' practice | MenkyoNavi'"
    description="Practice examination session."
    robots="noindex, nofollow"
>
    <livewire:exam-runner :attempt="$attempt" />
</x-layouts.exam>
