<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AgencyController extends Controller
{
    /**
     * Display the main dashboard with filters and pagination.
     */
    public function index(Request $request)
    {
        // Start with a base query — eager load services relationship
        $query = Agency::with('services');

        // Filter: text search (name, email, city)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filter: country dropdown
        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }

        // Filter: tech stack / service
        if ($service = $request->get('service')) {
            $query->whereHas('services', function ($q) use ($service) {
                $q->where('name', $service);
            });
        }

        // Filter: has email only
        if ($request->get('has_email')) {
            $query->whereNotNull('email')->where('email', '!=', '');
        }

        // Paginate results — 20 per page, preserve filter params
        $agencies = $query->orderBy('name')
                          ->paginate(20)
                          ->withQueryString();

        // Stats for the summary cards at the top
        $stats = [
            'total'         => Agency::count(),
            'with_email'    => Agency::whereNotNull('email')
                                      ->where('email', '!=', '')->count(),
            'countries'     => Agency::distinct('country')
                                      ->whereNotNull('country')->count('country'),
        ];

        // Dropdown options for filters
        $countries = Agency::select('country')
                           ->whereNotNull('country')
                           ->distinct()
                           ->orderBy('country')
                           ->pluck('country');

        $services = Service::orderBy('name')->pluck('name');

        return view('agencies.index', compact(
            'agencies', 'stats', 'countries', 'services'
        ));
    }

    /**
     * Export filtered agencies as a CSV download.
     * Reads same filter params as index() — export matches active view.
     */
    
    public function exportCsv(Request $request)
    {
        $query = Agency::with('services');

        // Apply same filters as index()
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }
        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }
        if ($service = $request->get('service')) {
            $query->whereHas('services', function ($q) use ($service) {
                $q->where('name', $service);
            });
        }
        if ($request->get('has_email')) {
            $query->whereNotNull('email')->where('email', '!=', '');
        }

        $agencies = $query->orderBy('name')->get();

        // Stream CSV response — no file saved to disk
        $filename = 'agencies_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($agencies) {
            $handle = fopen('php://output', 'w');

            // CSV header row
            fputcsv($handle, [
                'Name', 'Website', 'Country', 'City',
                'Email', 'LinkedIn', 'GitHub',
                'Company Size', 'Clutch Rating', 'Services', 'Source',
            ]);

            // One row per agency
            foreach ($agencies as $agency) {
                fputcsv($handle, [
                    $agency->name,
                    $agency->website,
                    $agency->country,
                    $agency->city,
                    $agency->email,
                    $agency->linkedin_url,
                    $agency->github_url,
                    $agency->company_size,
                    $agency->clutch_rating,
                    $agency->services->pluck('name')->join(', '),
                    $agency->source,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
    /**
     * Trigger the Python scraper in the background.
     * Returns immediately — does not wait for scraper to finish.
     */
    public function runScraper(Request $request)
    {
        $source = $request->get('source', 'github'); // default: github

        // Build path to Python executable inside venv
        $pythonPath = base_path('scraper/venv/Scripts/python'); // Windows
        // On Linux/Mac use: base_path('scraper/venv/bin/python')

        $scriptPath = base_path('scraper/main.py');

        // Run in background — & at end means "do not wait"
        // Windows:
        $command = "start /B {$pythonPath} {$scriptPath} --source {$source}";
        // Linux/Mac alternative:
        // $command = "{$pythonPath} {$scriptPath} --source {$source} > /dev/null 2>&1 &";

        exec($command);

        return response()->json([
            'message' => "Scraper started for source: {$source}",
            'status'  => 'running',
        ]);
    }

} // end class AgencyController

