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
        // Build the same filtered query as index()
        $query = Agency::with('services');

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

        $filename = 'agencies_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'              => 'text/csv; charset=UTF-8',
            'Content-Disposition'       => "attachment; filename=\"{$filename}\"",
            'X-Accel-Buffering'         => 'no',   // prevents Nginx buffering the stream
            'Cache-Control'             => 'no-cache',
        ];

        // Clone query before chunking so filters are preserved
        $exportQuery = clone $query;

        $callback = function () use ($exportQuery) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM — makes Excel open the file correctly without encoding issues
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Name', 'Website', 'Country', 'City',
                'Email', 'LinkedIn', 'GitHub',
                'Company Size', 'Clutch Rating', 'Services', 'Source',
            ]);

            // Process in chunks of 500 — never loads full dataset into RAM
            $exportQuery->orderBy('name')->chunk(500, function ($agencies) use ($handle) {
                foreach ($agencies as $agency) {
                    fputcsv($handle, [
                        $agency->name,
                        $agency->website,
                        $agency->country,
                        $agency->city,
                        $agency->email          ?? '',
                        $agency->linkedin_url   ?? '',
                        $agency->github_url     ?? '',
                        $agency->company_size   ?? '',
                        $agency->clutch_rating  ?? '',
                        $agency->services->pluck('name')->join(', '),
                        $agency->source,
                    ]);
                }
            });

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
        $source = $request->get('source', 'github');

        // Validate source to prevent command injection
        $allowed = ['github', 'clutch', 'goodfirms'];
        if (!in_array($source, $allowed)) {
            return response()->json(['message' => 'Invalid source.', 'status' => 'error'], 400);
        }

        $pythonPath = base_path('scraper/venv/Scripts/python.exe'); // Windows — note .exe
        $scriptPath = base_path('scraper/main.py');
        $logPath    = base_path('scraper/logs/scraper_run.log');

        // popen() works reliably from Apache/web context on Windows
        // Redirect both stdout and stderr to log file so you can debug
        $command = "\"$pythonPath\" \"$scriptPath\" --source $source >> \"$logPath\" 2>&1";

        popen("start /B " . $command, "r");

        return response()->json([
            'message' => "Scraper started for source: {$source}. Check logs/scraper_run.log for output.",
            'status'  => 'running',
        ]);
    }

} // end class AgencyController

