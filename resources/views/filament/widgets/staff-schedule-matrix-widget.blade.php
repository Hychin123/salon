<x-filament-widgets::widget>
    <x-filament::section heading="Staff Scheduling">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:separate; border-spacing:8px;">
                <thead>
                    <tr>
                        <th style="text-align:left; padding:6px 8px; min-width:140px;">Staff</th>
                        @foreach ($days as $day)
                            <th style="text-align:center; padding:6px 8px;">{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffRows as $row)
                        <tr>
                            <td style="padding:6px 8px; font-weight:600;">{{ $row['name'] }}</td>
                            @foreach ($row['cells'] as $cell)
                                <td style="padding:0;">
                                    @php
                                        $styles = match ($cell['type']) {
                                            'working' => 'background:#065f46;color:#d1fae5;border:1px solid #10b981;',
                                            'full' => 'background:#7c2d12;color:#ffedd5;border:1px solid #f97316;',
                                            default => 'background:#374151;color:#e5e7eb;border:1px solid #6b7280;',
                                        };
                                    @endphp
                                    <div style="{{ $styles }} border-radius:10px; padding:7px 8px; text-align:center; font-size:13px; font-weight:600;">
                                        {{ $cell['text'] }}
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:12px 8px; color:#6b7280;">No active staff found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
