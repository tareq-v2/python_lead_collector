# scraper/database/db_manager.py
# Handles all database read/write operations for the Python scraper.
# Uses SQLAlchemy to connect to MySQL (same database as Laravel).

import sys
import os

# Add the scraper root folder to Python's search path
# This lets us import from config/ and other folders
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker
from loguru import logger
from config.settings import DATABASE


def get_connection_url() -> str:
    """Build the MySQL connection URL from settings."""
    db = DATABASE
    return (
        f"mysql+pymysql://{db['user']}:{db['password']}"
        f"@{db['host']}:{db['port']}/{db['name']}"
        f"?charset=utf8mb4"
    )


class DBManager:
    """
    Manages all database operations for the scraper.

    Usage:
        db = DBManager()
        agency_id = db.upsert_agency({...})
        db.add_services(agency_id, ['Laravel', 'React'])
        db.close()
    """

    def __init__(self):
        self.engine  = create_engine(get_connection_url(), pool_pre_ping=True)
        Session      = sessionmaker(bind=self.engine)
        self.session = Session()
        logger.info('Database connection established.')


    # ── Main method: save an agency to the database ───────────────

    def upsert_agency(self, data: dict) -> int | None:
        """
        Insert a new agency or update it if website already exists.
        Returns the agency's database ID, or None if failed.

        The 'website' field is used as the unique identifier.
        This prevents duplicate rows when scraping the same agency twice.
        """

        # Clean the website URL
        # website = (data.get('website') or '').strip().rstrip('/')
        website = (data.get('website') or data.get('github_url') or '').strip().rstrip('/')

        # Skip agencies with no website (can't deduplicate without it)
        if not website:
            logger.warning(f"Skipping agency with no website: {data.get('name')}")
            return None

        try:
            # Check if this website already exists in the database
            existing = self.session.execute(
                text('SELECT id FROM agencies WHERE website = :w'),
                {'w': website}
            ).fetchone()

            if existing:
                # ── UPDATE existing record ────────────────────────
                agency_id = existing[0]
                self.session.execute(text('''
                    UPDATE agencies
                    SET
                        name            = :name,
                        country         = COALESCE(:country, country),
                        city            = COALESCE(:city, city),
                        email           = COALESCE(:email, email),
                        github_url      = COALESCE(:github_url, github_url),
                        company_size    = COALESCE(:company_size, company_size),
                        description     = COALESCE(:description, description),
                        source          = :source,
                        last_scraped_at = NOW()
                    WHERE id = :id
                '''), {**data, 'website': website, 'id': agency_id})
                logger.debug(f"Updated: {data.get('name')} (id={agency_id})")

            else:
                # ── INSERT new record ─────────────────────────────
                self.session.execute(text('''
                    INSERT INTO agencies
                        (name, website, country, city, email,
                         github_url, company_size, description,
                         source, last_scraped_at)
                    VALUES
                        (:name, :website, :country, :city, :email,
                         :github_url, :company_size, :description,
                         :source, NOW())
                '''), {**data, 'website': website})

                # MySQL uses LAST_INSERT_ID() to get the new row's ID
                agency_id = self.session.execute(
                    text('SELECT LAST_INSERT_ID()')
                ).scalar()
                logger.info(f"Inserted: {data.get('name')} (id={agency_id})")

            self.session.commit()
            return agency_id

        except Exception as e:
            self.session.rollback()
            logger.error(f"DB error for {data.get('name')}: {e}")
            return None


    # ── Save tech stack / services ────────────────────────────────

    def add_services(self, agency_id: int, service_names: list):
        """
        Link a list of service names to an agency.
        Creates the service if it does not exist yet.
        Example: add_services(1, ['Laravel', 'React', 'MySQL'])
        """
        for name in service_names:
            if not name or not name.strip():
                continue
            name = name.strip()
            try:
                # Get existing service or create it
                row = self.session.execute(
                    text('SELECT id FROM services WHERE name = :n'),
                    {'n': name}
                ).fetchone()

                if row:
                    service_id = row[0]
                else:
                    self.session.execute(
                        text('INSERT INTO services (name) VALUES (:n)'),
                        {'n': name}
                    )
                    service_id = self.session.execute(
                        text('SELECT LAST_INSERT_ID()')
                    ).scalar()

                # Link agency to service (INSERT IGNORE avoids duplicates)
                self.session.execute(text('''
                    INSERT IGNORE INTO agency_services
                    (agency_id, service_id)
                    VALUES (:a, :s)
                '''), {'a': agency_id, 's': service_id})

                self.session.commit()

            except Exception as e:
                self.session.rollback()
                logger.error(f"Service error '{name}': {e}")


    def get_existing_websites(self) -> set:
        """Return a set of all website URLs already in the database."""
        with self.engine.connect() as conn:
            result = conn.execute(text("SELECT website FROM agencies WHERE website IS NOT NULL AND website != ''"))
            return {row[0].rstrip('/') for row in result}

    def get_existing_github_urls(self) -> set:
        """Return a set of all github_urls already in the database."""
        with self.engine.connect() as conn:
            result = conn.execute(text("SELECT github_url FROM agencies WHERE github_url IS NOT NULL AND github_url != ''"))
            return {row[0] for row in result}

    # ── Write a scrape audit log ──────────────────────────────────

    def log_scrape(self, source: str, status: str,
                   records: int = 0, error: str = None):
        """Record that a scraper run happened with its result."""
        try:
            self.session.execute(text('''
                INSERT INTO scrape_logs
                    (source, status, records_found, error_message, scraped_at)
                VALUES
                    (:src, :st, :rec, :err, NOW())
            '''), {'src': source, 'st': status,
                   'rec': records, 'err': error})
            self.session.commit()
        except Exception as e:
            logger.error(f'Log error: {e}')


    def close(self):
        """Always call this when done to release the DB connection."""
        self.session.close()
        logger.info('Database connection closed.')
