"""Main entry point for the bot."""

import sys

# Check Python version before importing anything else
if sys.version_info < (3, 9):
    print(f"Error: Python 3.9+ is required, but you're using Python {sys.version_info.major}.{sys.version_info.minor}")
    print("Please use 'python3.9' or 'python3' instead of 'python'")
    sys.exit(1)

import asyncio
import logging
import platform
from typing import Optional

import discord

from ..config.settings import Settings, init_settings
from ..database.pool import DatabasePool, init_pool
from ..utils.logging import setup_logging
from ..utils.exceptions import ConfigError, DatabaseError
from .client import BotClient

logger: Optional[logging.Logger] = None


async def status_report_task(settings: Settings) -> None:
    """Background task to report bot status."""
    if not settings.division.enable_status_report:
        logger.info("Status report service is disabled")
        return
    
    import aiohttp
    from aiohttp import ClientTimeout, ClientError
    
    url = f"{settings.division.status_report_url}?bot_discord_id={settings.discord.bot_id}"
    timeout = ClientTimeout(total=5)
    first_error_logged = False
    
    while True:
        try:
            async with aiohttp.ClientSession(timeout=timeout) as session:
                async with session.post(url) as response:
                    if response.status == 200:
                        first_error_logged = False
                    elif not first_error_logged:
                        logger.warning(f"Status report returned status {response.status}")
                        first_error_logged = True
        except ClientError as e:
            if not first_error_logged:
                logger.warning(
                    f"Status report service unavailable. "
                    f"This is expected if the service is not running. Error: {type(e).__name__}"
                )
                first_error_logged = True
        except Exception as e:
            if not first_error_logged:
                logger.error(f"Unexpected error in status_report: {e}")
                first_error_logged = True
        
        await asyncio.sleep(settings.division.status_report_interval)


async def main() -> None:
    """Main bot function."""
    global logger
    
    # Determine if debug mode
    debug = platform.system() == "Darwin" or "--debug" in sys.argv
    
    try:
        # Load settings
        settings = init_settings(debug=debug)
        
        # Setup logging
        log_dir = "discord-bot" if platform.system() == "Darwin" else "."
        logger = setup_logging(
            log_level=settings.log_level,
            log_file="discord.log",
            log_dir=log_dir
        )
        
        logger.info(f"Starting bot (debug={debug})")
        
        # Initialize database pool
        db_pool = init_pool(settings.database)
        pool = await db_pool.create_pool()
        
        # Check database connection
        if not await db_pool.check_connection():
            logger.critical("Database connection failed, shutting down...")
            return
        
        # Create bot client
        bot = BotClient(settings)
        
        # Store pool in bot for access by cogs
        bot.db_pool = db_pool
        
        # Determine which token to use
        token = settings.discord.debug_token if debug else settings.discord.token
        
        # Start status report task
        status_task = asyncio.create_task(status_report_task(settings))
        
        try:
            # Start bot
            await bot.start(token, reconnect=True)
        finally:
            # Cleanup
            status_task.cancel()
            try:
                await status_task
            except asyncio.CancelledError:
                pass
            
            await db_pool.close_pool()
            await bot.close()
            
    except ConfigError as e:
        print(f"Configuration error: {e}")
        sys.exit(1)
    except DatabaseError as e:
        if logger:
            logger.critical(f"Database error: {e}")
        else:
            print(f"Database error: {e}")
        sys.exit(1)
    except KeyboardInterrupt:
        if logger:
            logger.info("Bot stopped by user")
        else:
            print("Bot stopped by user")
    except Exception as e:
        if logger:
            logger.exception(f"Unexpected error: {e}")
        else:
            print(f"Unexpected error: {e}")
        sys.exit(1)


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\nBot stopped by user")

