<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunScraper extends Command
{
    protected $signature   = 'scraper:run {source=github}';
    protected $description = 'Run the Python scraper for the specified source';

    public function handle(): void
    {
        $source     = $this->argument('source');
        $pythonPath = base_path('scraper/venv/Scripts/python'); // Windows
        $scriptPath = base_path('scraper/main.py');

        $this->info("Starting {$source} scraper...");

        $command = "{$pythonPath} {$scriptPath} --source {$source}";
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            $this->info("Scraper completed successfully.");
        } else {
            $this->error("Scraper exited with code {$exitCode}");
        }

        foreach ($output as $line) {
            $this->line($line);
        }
    }
}
