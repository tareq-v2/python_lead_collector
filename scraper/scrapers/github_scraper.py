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
    1. For each keyword in GITHUB_QUERIES, search GitHub organizations
    2. For each result, fetch the full organization profile
    3. Map the GitHub data to our agency schema
    4. Return list of agency dicts ready for database insertion
    """

    def __init__(self):
        # Set up request headers
        self.headers = {
            'Accept':              'application/vnd.github+json',
            'X-GitHub-Api-Version': '2022-11-28',
        }

        # Add token if available (increases rate limit from 60 to 5000/hr)
        if GITHUB_TOKEN:
            self.headers['Authorization'] = f'Bearer {GITHUB_TOKEN}'
            logger.info('GitHub: using token (5,000 requests/hour)')
        else:
            logger.warning('GitHub: no token set — limited to 60 requests/hour')
            logger.warning('Add GITHUB_TOKEN to scraper/.env to increase limit')


    # ── Main method ───────────────────────────────────────────────

    def scrape(self) -> list[dict]:
        """
        Run all search queries and return a list of agency dicts.
        Automatically skips organizations already seen (deduplication).
        """
        seen    = set()   # Track org logins we have already processed
        results = []

        for query in GITHUB_QUERIES:
            logger.info(f'GitHub searching: "{query}"')

            orgs = self._search_organizations(query)
            logger.info(f'  Found {len(orgs)} organizations')

            for org_stub in orgs:
                login = org_stub.get('login')

                # Skip if already processed in a previous query
                if login in seen:
                    continue
                seen.add(login)

                # Small delay between requests to be polite
                time.sleep(random.uniform(0.3, 0.8))

                # Fetch the full organization details
                full_org = self._get_org_details(login)
                if not full_org:
                    continue

                # Convert GitHub data to our agency format
                agency = self._map_to_agency(full_org)
                if agency:
                    results.append(agency)
                    logger.info(
                        f"  + {agency['name']:<35}"
                        f"| {agency['country'] or 'Unknown':<20}"
                        f"| {agency['website'] or 'No website'}"
                    )

            # Pause between different search queries
            logger.info(f'  Query done. Waiting 2 seconds...')
            time.sleep(2)

        logger.success(f'GitHub scraper complete — {len(results)} agencies collected')
        return results


    # ── API call: search for organizations ───────────────────────

    def _search_organizations(self, query: str, per_page: int = 30) -> list:
        """
        Search GitHub for organizations matching a query.
        Returns up to per_page org stubs (just login + URL, not full data).
        """
        try:
            response = requests.get(
                f'{GITHUB_API}/search/users',
                headers=self.headers,
                params={
                    'q':        f'{query} type:org',
                    'per_page': per_page,
                    'sort':     'repositories',
                },
                timeout=10,
            )

            # Check rate limit headers
            self._check_rate_limit(response)

            response.raise_for_status()
            return response.json().get('items', [])

        except Exception as e:
            logger.error(f'Search failed for "{query}": {e}')
            return []


    # ── API call: get full organization details ───────────────────

    def _get_org_details(self, login: str) -> dict | None:
        """
        Fetch complete organization profile.
        This contains website, email, location, description.
        """
        try:
            response = requests.get(
                f'{GITHUB_API}/orgs/{login}',
                headers=self.headers,
                timeout=10,
            )

            if response.status_code == 404:
                return None   # Org was deleted or is private

            self._check_rate_limit(response)
            response.raise_for_status()
            return response.json()

        except Exception as e:
            logger.debug(f'Could not fetch org {login}: {e}')
            return None


    # ── Rate limit handler ────────────────────────────────────────

    def _check_rate_limit(self, response):
        """
        Check GitHub rate limit headers and sleep if running low.
        GitHub returns X-RateLimit-Remaining and X-RateLimit-Reset
        in every response header.
        """
        remaining = int(response.headers.get('X-RateLimit-Remaining', 99))

        if remaining < 5:
            reset_at  = int(response.headers.get('X-RateLimit-Reset',
                                                   time.time() + 60))
            wait_secs = max(0, reset_at - time.time()) + 5
            logger.warning(
                f'GitHub rate limit almost reached!'
                f'Waiting {wait_secs:.0f} seconds...'
            )
            time.sleep(wait_secs)


    # ── Data mapper: GitHub org → our agency schema ───────────────

    def _map_to_agency(self, org: dict) -> dict | None:
        """
        Convert a GitHub organization object into our agency dict format.
        Returns None if the org doesn't look like a real software company.
        """

        # Filter out orgs with no website AND no location
        # (these are usually open source projects, not companies)
        if not org.get('blog') and not org.get('location'):
            return None

        # Parse location: 'Dhaka, Bangladesh' → city='Dhaka', country='Bangladesh'
        location = org.get('location') or ''
        parts    = [p.strip() for p in location.split(',')]
        city     = parts[0] if parts else ''
        country  = parts[-1] if len(parts) > 1 else location

        # Clean up website URL
        website = (org.get('blog') or '').strip()
        if website and not website.startswith('http'):
            website = 'https://' + website
        website = website.rstrip('/')

        # Estimate company size from number of public repos
        # (not perfect but gives a rough indicator)
        repos = org.get('public_repos', 0)
        if   repos < 5:   size = '1-10'
        elif repos < 20:  size = '11-50'
        elif repos < 60:  size = '51-200'
        else:             size = '200+'

        return {
            'name':         org.get('name') or org.get('login'),
            'website':      website,
            'email':        org.get('email') or None,
            'country':      country or None,
            'city':         city or None,
            'github_url':   org.get('html_url'),
            'company_size': size,
            'description':  (org.get('description') or '')[:500],
            'clutch_url':   None,
            'source':       'github',
            # services will be added separately
            'services':     [],
        }
