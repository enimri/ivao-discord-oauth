"""Discord bot client setup."""

import logging
import discord
from discord.ext import commands, tasks

from ..config.settings import Settings, get_settings
from ..database.pool import get_pool

logger = logging.getLogger("discord")


class BotClient(commands.Bot):
    """Extended Discord bot client with custom functionality."""
    
    def __init__(self, settings: Settings):
        """
        Initialize bot client.
        
        Args:
            settings: Application settings
        """
        intents = discord.Intents.all()
        activity = discord.Activity(
            name=f'IVAO {settings.division.country}',
            type=discord.ActivityType.watching
        )
        
        super().__init__(
            command_prefix='.',
            intents=intents,
            activity=activity,
            help_command=None
        )
        
        self.settings = settings
        self._extensions_loaded = False
    
    async def setup_hook(self) -> None:
        """Called when the bot is setting up."""
        await self.load_extensions()
    
    async def load_extensions(self) -> None:
        """Load all bot extensions (cogs)."""
        if self._extensions_loaded:
            return
        
        import os
        cogs_dir = os.path.join(os.path.dirname(__file__), "..", "cogs")
        
        for filename in os.listdir(cogs_dir):
            if filename.endswith('.py') and not filename.startswith('_'):
                cog_name = f"src.cogs.{filename[:-3]}"
                try:
                    await self.load_extension(cog_name)
                    logger.info(f"Loaded extension: {cog_name}")
                except Exception as e:
                    logger.error(f"Failed to load extension {cog_name}: {e}")
        
        self._extensions_loaded = True
    
    async def on_ready(self) -> None:
        """Called when the bot is ready."""
        logger.info("Bot is ready!")
        logger.info(f"Logged in as {self.user} (ID: {self.user.id})")
        
        # Sync command tree
        try:
            synced = await self.tree.sync()
            logger.info(f"Synced {len(synced)} command(s)")
        except Exception as e:
            logger.error(f"Failed to sync commands: {e}")
        
        # Start background tasks
        if not self.check_db_connection.is_running():
            self.check_db_connection.start()
    
    @tasks.loop(minutes=5)
    async def check_db_connection(self) -> None:
        """Periodically check database connection health."""
        try:
            pool = get_pool()
            if not await pool.ping_pool():
                logger.warning("Database connection unhealthy, attempting to recreate pool")
        except Exception as e:
            logger.error(f"Database health check failed: {e}")
    
    @check_db_connection.before_loop
    async def before_db_check(self) -> None:
        """Wait until bot is ready before starting DB checks."""
        await self.wait_until_ready()
    
    def is_bot_manager(self, user_id: int) -> bool:
        """Check if a user is a bot manager."""
        return self.settings.is_bot_manager(user_id)

