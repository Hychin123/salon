<x-filament-widgets::widget>
    <x-filament::section :heading="'Appointments — ' . $selectedDateLabel">
        <div class="space-y-3">
            @forelse ($appointments as $appointment)
                <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 dark:border-white/10">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ \Carbon\Carbon::parse($appointment->start_time)->format('H:i') }}
                        </span>
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-[#6804A5]"></span>
                        <div>
                            <p class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ $appointment->client?->name ?? 'Guest' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $appointment->service?->name ?? 'Service' }} · {{ $appointment->staff?->name ?? 'Staff' }}
                            </p>
                        </div>
                    </div>

                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                        {{ $appointment->status }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No appointments for this date.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
