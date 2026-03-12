# scraper/main.py
# Entry point for the Agency Lead Scraper.
# Run this file to start scraping.
#
# Usage:
#   python main.py --source github
#   python main.py --source github clutch   (multiple sources)

import sys
import os
import time
import argparse

# Add the scraper folder to Python path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from loguru import logger
from tqdm import tqdm

from scrapers.github_scraper import GitHubScraper
from database.db_manager import DBManager


# ── Configure logging ─────────────────────────────────────────────
os.makedirs('logs', exist_ok=True)

# Remove default logger and add our custom ones
logger.remove()

# Console output: colored, easy to read
logger.add(
    sys.stdout,
    level='INFO',
    colorize=True,
    format='<green>{time:HH:mm:ss}</green> | <level>{level:<8}</level> | {message}',
)

# File output: full detail, saved to logs/scraper.log
logger.add(
    'logs/scraper.log',
    level='DEBUG',
    rotation='5 MB',
    retention='14 days',
    format='{time:YYYY-MM-DD HH:mm:ss} | {level:<8} | {message}',
)


# ── Scraper registry ──────────────────────────────────────────────
# Add new scrapers here as you build them
SCRAPERS = {
    'github': GitHubScraper,
    # 'clutch': ClutchScraper,   # will add in Phase 3
}


# ── Main pipeline ─────────────────────────────────────────────────

def run(sources: list = None) -> int:
    """
    Main pipeline: scrape → save to database → log results.
    Returns total number of agencies saved.
    """
    db      = DBManager()
    sources = sources or list(SCRAPERS.keys())
    total   = 0

    for source in sources:

        if source not in SCRAPERS:
            logger.warning(f'Unknown source: "{source}" — skipping')
            logger.info(f'Available sources: {list(SCRAPERS.keys())}')
            continue

        logger.info(f'')
        logger.info(f'{'='*50}')
        logger.info(f'  Running scraper: {source.upper()}')
        logger.info(f'{'='*50}')

        t_start = time.time()

        # ── Run the scraper ───────────────────────────────────────
        try:
            scraper  = SCRAPERS[source]()
            agencies = scraper.scrape()
        except Exception as e:
            logger.error(f'Scraper {source} crashed: {e}')
            db.log_scrape(source, 'failed', error=str(e))
            continue

        # ── Save each agency to the database ─────────────────────
        saved = 0
        for agency in tqdm(agencies, desc=f'Saving {source} agencies'):

            # Pull out services before passing to upsert
            # (services go in a separate table)
            services = agency.pop('services', [])

            # Save the agency (insert or update)
            agency_id = db.upsert_agency(agency)

            if agency_id:
                # Link the tech stack services
                if services:
                    db.add_services(agency_id, services)
                saved += 1

        # ── Log the run results ───────────────────────────────────
        duration_ms = int((time.time() - t_start) * 1000)
        db.log_scrape(source, 'success', records=saved)

        logger.success(
            f'{source}: saved {saved}/{len(agencies)} agencies'
            f' in {duration_ms/1000:.1f}s'
        )
        total += saved

    db.close()

    logger.info(f'')
    logger.success(f'All done! {total} agencies saved to database.')
    print(f'\n✅ Complete: {total} agencies saved to MySQL database.')
    return total


# ── Command line interface ────────────────────────────────────────

if __name__ == '__main__':
    parser = argparse.ArgumentParser(
        description='Agency Lead Scraper',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog='''
Examples:
  python main.py --source github
  python main.py --source github clutch
        '''
    )
    parser.add_argument(
        '--source',
        nargs='+',
        help='Which scraper(s) to run. Options: github, clutch',
        default=['github'],
    )
    args = parser.parse_args()
    run(sources=args.source)
