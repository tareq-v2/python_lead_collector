# scraper/scrapers/github_scraper.py
# Scrapes software agency organizations from GitHub's free public API.
# GitHub API documentation: docs.github.com/en/rest

import sys
import os
import time
import random
import requests

# Add scraper root to Python path so we can import config
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from loguru import logger
from config.settings import GITHUB_TOKEN, GITHUB_QUERIES, USER_AGENTS


GITHUB_API = 'https://api.github.com'


class GitHubScraper:
    """
    Searches GitHub for software agency organizations.

    How it works:
    1. Receives already_saved set from main.py (no DB connection here)
    2. For each keyword in GITHUB_QUERIES, search GitHub organizations
    3. Skips orgs whose github_url is already in the database
    4. For each new org, fetches the full organization profile
    5. Maps the GitHub data to our agency schema
    6. Returns list of agency dicts ready for database insertion
    """

    def __init__(self):
        # Set up request headers — these are sent with every API call
        self.headers = {
            'Accept':               'application/vnd.github+json',
            'X-GitHub-Api-Version': '2022-11-28',
        }

        # Add token if available (increases rate limit from 60 to 5,000/hr)
        if GITHUB_TOKEN:
            self.headers['Authorization'] = f'Bearer {GITHUB_TOKEN}'
            logger.info('GitHub: using token (5,000 requests/hour)')
        else:
            logger.warning('GitHub: no token set — limited to 60 requests/hour')
            logger.warning('Add GITHUB_TOKEN to scraper/.env to increase limit')


    # ── Main entry point ──────────────────────────────────────────

    def scrape(self, already_saved: set = None) -> list[dict]:
        """
        Run all search queries and return a list of NEW agency dicts.

        Args:
            already_saved: Set of github_url strings already in the database.
                           Passed in from main.py — no DB connection needed here.
                           If None, no skipping is done (first run behaviour).

        Returns:
            List of agency dicts — only orgs not already in the database.
        """
        already_saved = already_saved or set()

        logger.info(f'GitHub: {len(already_saved)} orgs already in database — will skip these')

        seen    = set()   # logins seen this session (cross-query dedup)
        results = []

        for query in GITHUB_QUERIES:
            logger.info(f'')
            logger.info(f'GitHub searching: "{query}"')

            orgs = self._search_organizations(query)
            logger.info(f'  Found {len(orgs)} organizations in search results')

            new_count     = 0
            skipped_count = 0

            for org_stub in orgs:
                login    = org_stub.get('login', '')
                html_url = org_stub.get('html_url', '')

                # ── Skip: already processed this session ─────────
                if login in seen:
                    logger.debug(f'  Skip (session duplicate): {login}')
                    skipped_count += 1
                    continue
                seen.add(login)

                # ── Skip: already in database from a previous run ─
                if html_url in already_saved:
                    logger.debug(f'  Skip (in database): {login}')
                    skipped_count += 1
                    continue

                # ── Polite delay between API calls ────────────────
                time.sleep(random.uniform(0.4, 1.0))

                # ── Fetch full organization profile ───────────────
                full_org = self._get_org_details(login)
                if not full_org:
                    continue

                # ── Map to our agency schema ──────────────────────
                agency = self._map_to_agency(full_org)
                if not agency:
                    logger.debug(f'  Skip (no website/location): {login}')
                    continue

                results.append(agency)
                new_count += 1

                logger.info(
                    f"  + {agency['name']:<35}"
                    f"  {agency['country'] or 'Unknown country':<20}"
                    f"  {agency['website'] or 'no website'}"
                )

            logger.info(
                f'  Query done — {new_count} new, {skipped_count} skipped. '
                f'Waiting 2 seconds...'
            )
            time.sleep(2)

        logger.success(
            f'GitHub scraper complete — '
            f'{len(results)} new agencies collected, '
            f'{len(already_saved)} already in database were skipped'
        )
        return results


    # ── API: search for organizations ─────────────────────────────

    def _search_organizations(self, query: str, per_page: int = 30) -> list:
        """
        Search GitHub for organizations matching a keyword query.

        Returns up to per_page org stubs.
        Each stub contains: login, html_url, avatar_url, type.
        Full details (website, email, location) are fetched separately.
        """
        try:
            response = requests.get(
                f'{GITHUB_API}/search/users',
                headers=self.headers,
                params={
                    'q':        f'{query} type:org',
                    'per_page': per_page,
                    'sort':     'repositories',
                    'order':    'desc',
                },
                timeout=15,
            )

            self._check_rate_limit(response)

            if response.status_code == 422:
                logger.warning(f'Search query rejected by GitHub: "{query}"')
                return []

            response.raise_for_status()
            items = response.json().get('items', [])
            return items

        except requests.exceptions.Timeout:
            logger.error(f'Search timed out for query: "{query}"')
            return []
        except Exception as e:
            logger.error(f'Search failed for "{query}": {e}')
            return []


    # ── API: fetch full organization profile ──────────────────────

    def _get_org_details(self, login: str) -> dict | None:
        """
        Fetch a complete organization profile from the GitHub API.

        The search endpoint only returns basic stubs. This call gives us:
        website (blog), email, location, description, public_repos count.
        """
        try:
            response = requests.get(
                f'{GITHUB_API}/orgs/{login}',
                headers=self.headers,
                timeout=15,
            )

            # Org was deleted, renamed, or is actually a user not an org
            if response.status_code == 404:
                logger.debug(f'Org not found: {login}')
                return None

            # Org exists but we don't have permission (private org)
            if response.status_code == 403:
                logger.debug(f'Org is private: {login}')
                return None

            self._check_rate_limit(response)
            response.raise_for_status()
            return response.json()

        except requests.exceptions.Timeout:
            logger.warning(f'Timeout fetching org details: {login}')
            return None
        except Exception as e:
            logger.debug(f'Could not fetch org {login}: {e}')
            return None


    # ── Rate limit guard ──────────────────────────────────────────

    def _check_rate_limit(self, response: requests.Response) -> None:
        """
        Read GitHub rate limit headers from the response.
        If running low (< 5 requests remaining), sleep until the
        rate limit window resets.

        GitHub sends these headers on every response:
            X-RateLimit-Remaining  — how many requests left this window
            X-RateLimit-Reset      — Unix timestamp when window resets
        """
        remaining = int(response.headers.get('X-RateLimit-Remaining', 99))

        if remaining < 5:
            reset_at  = int(response.headers.get('X-RateLimit-Reset',
                                                   time.time() + 60))
            wait_secs = max(0, reset_at - time.time()) + 5   # +5 buffer

            logger.warning(
                f'GitHub rate limit almost reached ({remaining} left)! '
                f'Sleeping {wait_secs:.0f} seconds until reset...'
            )
            time.sleep(wait_secs)

        elif remaining < 20:
            # Getting close — add extra delay between requests
            logger.debug(f'Rate limit getting low: {remaining} remaining')
            time.sleep(2)


    # ── Data mapper: GitHub org → agency schema ───────────────────

    def _map_to_agency(self, org: dict) -> dict | None:
        """
        Convert a raw GitHub organization API response into our agency dict.

        Filters out orgs that are clearly not software companies:
        — No website AND no location → likely an open source project
        — Name is empty → broken record

        Returns None if the org should be skipped.
        """
        name = (org.get('name') or org.get('login') or '').strip()
        if not name:
            return None

        # Must have at least one of: website or location
        # Pure open source projects typically have neither
        has_website  = bool(org.get('blog', '').strip())
        has_location = bool(org.get('location', '').strip())
        if not has_website and not has_location:
            return None

        # ── Parse location ────────────────────────────────────────
        # GitHub location is a free text field — format varies wildly:
        #   'Dhaka, Bangladesh'  → city=Dhaka,      country=Bangladesh
        #   'Bangladesh'         → city='',          country=Bangladesh
        #   'New York, NY, USA'  → city=New York,   country=USA
        location = (org.get('location') or '').strip()
        parts    = [p.strip() for p in location.split(',') if p.strip()]
        city     = parts[0]  if len(parts) >= 2 else ''
        country  = parts[-1] if len(parts) >= 1 else ''

        # ── Clean website URL ─────────────────────────────────────
        website = (org.get('blog') or '').strip()
        if website:
            # Some orgs write their URL without the scheme
            if not website.startswith(('http://', 'https://')):
                website = 'https://' + website
            website = website.rstrip('/')
        else:
            website = ''

        # ── Estimate company size from public repo count ──────────
        # Not precise but gives a useful rough indicator
        repos = org.get('public_repos', 0)
        if   repos == 0:  size = '1-10'
        elif repos < 5:   size = '1-10'
        elif repos < 20:  size = '11-50'
        elif repos < 60:  size = '51-200'
        else:             size = '200+'

        return {
            'name':         name,
            'website':      website or None,
            'email':        org.get('email') or None,
            'country':      country or None,
            'city':         city or None,
            'github_url':   org.get('html_url') or None,
            'linkedin_url': None,
            'clutch_url':   None,
            'company_size': size,
            'description':  (org.get('description') or '').strip()[:500] or None,
            'source':       'github',
            'services':     [],   # populated separately via add_services()
        }