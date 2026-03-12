@extends('layouts.app')
@section('title', 'Agencies')

@section('content')

    {{-- 1. Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-primary">Software Agency Database</h1>
        <p class="text-gray-500 text-sm mt-1">Browse, filter, and export scraped agency leads</p>
    </div>

    {{-- 2. Stats Cards (see Chapter 5) --}}
    @include('agencies._stats', ['stats' => $stats])

    {{-- 3. Filter Bar + Action Buttons (see Chapter 4) --}}
    @include('agencies._filters', [
        'countries' => $countries,
        'services' => $services,
    ])

    {{-- 4. Agency Table (see Chapter 6) --}}
    @include('agencies._table', ['agencies' => $agencies])

@endsection

@push('scripts')
    <script>
        // Scraper trigger button handler
        document.getElementById('run-scraper-btn')?.addEventListener('click', function() {

            const btn = this;

            btn.disabled = true;
            btn.textContent = 'Running scraper...';

            fetch('/agencies/scrape', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        source: 'github'
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server error');
                    }
                    return response.json();
                })
                .then(data => {

                    alert(data.message || 'Scraper started successfully.');

                    btn.disabled = false;
                    btn.textContent = '▶ Run Scraper';

                })
                .catch(error => {

                    console.error(error);

                    alert('Failed to start scraper.');

                    btn.disabled = false;
                    btn.textContent = '▶ Run Scraper';
                });

        });
    </script>
@endpush
