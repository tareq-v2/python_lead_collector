{{-- Result count summary --}}
<div class="flex justify-between items-center mb-3">
    <p class="text-sm text-gray-500">
        Showing {{ $agencies->firstItem() }}–{{ $agencies->lastItem() }}
        of {{ number_format($agencies->total()) }} agencies
    </p>
</div>

{{-- Main agency table --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-primary text-white">
            <tr>
                <th class="text-left px-4 py-3 font-semibold">Company</th>
                <th class="text-left px-4 py-3 font-semibold">Location</th>
                <th class="text-left px-4 py-3 font-semibold">Email</th>
                <th class="text-left px-4 py-3 font-semibold">Tech Stack</th>
                <th class="text-left px-4 py-3 font-semibold">Size</th>
                <th class="text-left px-4 py-3 font-semibold">Rating</th>
                <th class="text-left px-4 py-3 font-semibold">Source</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">

            @forelse ($agencies as $agency)
                <tr class="hover:bg-blue-50 transition-colors">

                    {{-- Company name + website --}}
                    <td class="px-4 py-3">
                        <div class="font-medium text-primary">
                            {{-- Phase 3: wrap in <a href="/agencies/{{ $agency->id }}"> --}}
                            {{ $agency->name }}
                        </div>
                        @if ($agency->website)
                            <a href="{{ $agency->website }}" target="_blank"
                                class="text-xs text-gray-400 hover:text-accent">
                                {{ Str::limit($agency->website, 40) }}
                            </a>
                        @endif
                    </td>

                    {{-- Location --}}
                    <td class="px-4 py-3 text-gray-600">
                        @if ($agency->city)
                            {{ $agency->city }},
                        @endif
                        {{ $agency->country ?? '—' }}
                    </td>

                    {{-- Email --}}
                    <td class="px-4 py-3">
                        @if ($agency->email)
                            <a href="mailto:{{ $agency->email }}" class="text-accent hover:underline text-xs">
                                {{ $agency->email }}
                            </a>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>

                    {{-- Tech stack badges --}}
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($agency->services->take(4) as $service)
                                <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">
                                    {{ $service->name }}
                                </span>
                            @endforeach
                            @if ($agency->services->count() > 4)
                                <span class="text-gray-400 text-xs">+{{ $agency->services->count() - 4 }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- Company size --}}
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ $agency->company_size ?? '—' }}
                    </td>

                    {{-- Clutch rating --}}
                    <td class="px-4 py-3 text-gray-600 text-xs">
                        {{ $agency->clutch_rating ? "⭐ {$agency->clutch_rating}" : '—' }}
                    </td>

                    {{-- Source badge --}}
                    <td class="px-4 py-3">
                        <span
                            class="text-xs px-2 py-0.5 rounded-full
                        {{ $agency->source === 'github' ? 'bg-gray-800 text-white' : '' }}
                        {{ $agency->source === 'clutch' ? 'bg-red-100 text-red-700' : '' }}
                        {{ $agency->source === 'goodfirms' ? 'bg-green-100 text-green-700' : '' }}
                    ">
                            {{ $agency->source }}
                        </span>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                        No agencies found matching your filters.
                    </td>
                </tr>
            @endforelse

        </tbody>
    </table>
</div>

{{-- Pagination links --}}
<div class="mt-4">
    {{ $agencies->links() }}
</div>
