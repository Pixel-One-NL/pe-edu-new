<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-x-3" wire:poll.10s="getJobStatus">
            <div class="flex-1">
                <h2
                    class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white"
                >
                    Master import
                </h2>

                @if($lastRun)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Laatste import: {{ $lastRun->format('d-m-Y H:i') }}
                    </p>
                @endif
            </div>

            <div
                wire:loading.class="opacity-50 pointer-events-none"
                :class="{ 'opacity-50 pointer-events-none': $wire.jobStatus == 'pending' || $wire.jobStatus == 'processing' }"
            >
                <x-filament::button
                    color="gray"
                    icon="heroicon-s-circle-stack"
                    labeled-from="sm"
                    tag="button"
                    type="submit"
                    wire:click="runImport"
                    :disabled="$jobStatus == 'pending' || $jobStatus == 'processing'"
                    wire:loading.attr="disabled"
                >
                    Importeren
                </x-filament::button>
            </div>

            @if($jobStatus == 'pending' || $jobStatus == 'processing')
                <x-filament::badge color="info">
                    <div class="inline-flex gap-x-2 items-center">
                        <x-filament::loading-indicator class="h-5 w-5" />
                        <span>Laden</span>
                    </div>
                </x-filament::badge>
            @endif

            @if($jobStatus == 'completed' && $lastRun->isToday())
                <x-filament::badge color="success">
                    Afgerond
                </x-filament::badge>
            @endif

            @if($jobStatus == 'failed' && $lastRun->isToday())
                <x-filament::badge color="danger">
                    Fout opgetreden
                </x-filament::badge>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
