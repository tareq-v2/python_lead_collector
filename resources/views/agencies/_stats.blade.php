<div class="grid grid-cols-3 gap-4 mb-6">

    {{-- Total Agencies card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total Agencies</p>
                <p class="text-3xl font-bold text-primary mt-1">
                    {{ number_format($stats['total']) }}
                </p>
            </div>
            <span class="text-4xl">🏢</span>
        </div>
    </div>

    {{-- With Email card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">With Email</p>
                <p class="text-3xl font-bold text-green-600 mt-1">
                    {{ number_format($stats['with_email']) }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    @if ($stats['total'] > 0)
                        {{ round(($stats['with_email'] / $stats['total']) * 100) }}% of total
                    @endif
                </p>
            </div>
            <span class="text-4xl">📧</span>
        </div>
    </div>

    {{-- Countries card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide">Countries</p>
                <p class="text-3xl font-bold text-accent mt-1">
                    {{ number_format($stats['countries']) }}
                </p>
            </div>
            <span class="text-4xl">🌍</span>
        </div>
    </div>

</div>
