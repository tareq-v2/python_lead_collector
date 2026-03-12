# scraper/config/settings.py
# All configuration for the scraper system

import os
from dotenv import load_dotenv

# Load .env file from the same folder as this settings.py
# dotenv_path points to scraper/.env
dotenv_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), '.env')
load_dotenv(dotenv_path)


# ── Database settings ────────────────────────────────────────────
# These must match your Laravel .env exactly
DATABASE = {
    'engine':   'mysql',
    'host':     os.getenv('DB_HOST',     '127.0.0.1'),
    'port':     int(os.getenv('DB_PORT', 3306)),
    'name':     os.getenv('DB_DATABASE', 'agency_db'),
    'user':     os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
}


# ── GitHub API token ─────────────────────────────────────────────
# Free token: github.com/settings/tokens (no scopes needed)
GITHUB_TOKEN = os.getenv('GITHUB_TOKEN', '')


# ── Rate limiting (requests per source) ──────────────────────────
# These control how fast the scraper runs
# Higher delay = slower but safer (less chance of being blocked)
RATE_LIMITS = {
    'github':  {'requests_per_minute': 30, 'delay_seconds': 1.0},
    'clutch':  {'requests_per_minute': 8,  'delay_seconds': 4.0},
    'default': {'requests_per_minute': 6,  'delay_seconds': 5.0},
}


# ── Browser User-Agent strings (rotated per request) ─────────────
USER_AGENTS = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
    '(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',

    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 '
    '(KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',

    'Mozilla/5.0 (X11; Linux x86_64; rv:123.0) Gecko/20100101 Firefox/123.0',
]


# ── Retry settings ────────────────────────────────────────────────
MAX_RETRIES    = 3
RETRY_WAIT_MIN = 2   # seconds
RETRY_WAIT_MAX = 10  # seconds


# ── Search queries for GitHub ─────────────────────────────────────
# These keywords are used to search GitHub for software agencies
GITHUB_QUERIES = [
    'laravel agency',
    'software house',
    'web development company',
    'react development agency',
    'php development company',
    'mobile app development',
]
