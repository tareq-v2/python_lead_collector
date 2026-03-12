<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" action="/agencies" class="flex flex-wrap gap-3 items-end">

        {{-- Search input --}}
        <div class="flex-1 min-w-48">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, email, city..."
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
        </div>

        {{-- Country dropdown --}}
        <div class="min-w-40">
            <label class="block text-xs text-gray-500 mb-1">Country</label>
            <select name="country"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                <option value="">All Countries</option>
                @foreach ($countries as $c)
                    <option value="{{ $c }}" {{ request('country') === $c ? 'selected' : '' }}>
                        {{ $c }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tech stack dropdown --}}
        <div class="min-w-40">
            <label class="block text-xs text-gray-500 mb-1">Tech Stack</label>
            <select name="service"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                <option value="">All Technologies</option>
                @foreach ($services as $s)
                    <option value="{{ $s }}" {{ request('service') === $s ? 'selected' : '' }}>
                        {{ $s }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Has Email checkbox --}}
        <div class="flex items-end pb-2">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="has_email" value="1" {{ request('has_email') ? 'checked' : '' }}
                    class="w-4 h-4 accent-accent">
                Has email only
            </label>
        </div>

        {{-- Submit (Filter) button --}}
        <div>
            <button type="submit"
                class="bg-accent text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-700 transition">
                🔍 Filter
            </button>
        </div>

        {{-- Clear filters link --}}
        <div>
            <a href="/agencies" class="text-sm text-gray-400 hover:text-gray-700 px-2 py-2 inline-block">
                ✕ Clear
            </a>
        </div>

    </form>

    {{-- Action buttons row (Export + Run Scraper) --}}
    <div class="flex gap-3 mt-3 pt-3 border-t border-gray-100">

        {{-- CSV Export link — preserves active filters --}}
        <a href="/agencies/export?{{ http_build_query(request()->except('page')) }}"
            class="inline-block bg-green-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-green-700 transition">
            ⬇ Export CSV
        </a>

        {{-- Run Scraper button — triggers via JavaScript (see Chapter 8) --}}
        <button id="run-scraper-btn"
            class="bg-primary text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-900 transition">
            ▶ Run Scraper
        </button>

    </div>
</div>
