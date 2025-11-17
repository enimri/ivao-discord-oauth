"""Database connection pool management."""

import logging
from typing import Optional
import aiomysql
from aiomysql import Pool

from ..config.settings import DatabaseConfig
from ..utils.exceptions import DatabaseError

logger = logging.getLogger("discord")


class DatabasePool:
    """Manages database connection pool."""
    
    def __init__(self, config: DatabaseConfig):
        """
        Initialize database pool.
        
        Args:
            config: Database configuration
        """
        self.config = config
        self._pool: Optional[Pool] = None
    
    async def create_pool(self) -> Pool:
        """
        Create and return a connection pool.
        
        Returns:
            Database connection pool
            
        Raises:
            DatabaseError: If pool creation fails
        """
        try:
            self._pool = await aiomysql.create_pool(
                host=self.config.host,
                port=self.config.port,
                user=self.config.user,
                password=self.config.password,
                db=self.config.database,
                minsize=self.config.min_size,
                maxsize=self.config.max_size,
                echo=False,
                pool_recycle=self.config.pool_recycle,
                autocommit=False
            )
            logger.info("Database connection pool created successfully")
            return self._pool
        except Exception as e:
            logger.error(f"Failed to create database pool: {e}")
            raise DatabaseError(f"Failed to create database pool: {e}") from e
    
    async def close_pool(self) -> None:
        """Close the connection pool."""
        if self._pool:
            self._pool.close()
            await self._pool.wait_closed()
            logger.info("Database connection pool closed")
            self._pool = None
    
    async def check_connection(self) -> bool:
        """
        Check if the database connection is healthy.
        
        Returns:
            True if connection is healthy, False otherwise
        """
        if not self._pool:
            return False
        
        try:
            async with self._pool.acquire() as conn:
                async with conn.cursor() as cursor:
                    await cursor.execute("SELECT 1")
                    result = await cursor.fetchone()
                    return result is not None
        except Exception as e:
            logger.error(f"Database connection check failed: {e}")
            return False
    
    async def ping_pool(self) -> bool:
        """
        Ping the database to keep connections alive.
        
        Returns:
            True if ping successful, False otherwise
        """
        if not await self.check_connection():
            logger.warning("Database ping failed, attempting to recreate pool")
            try:
                await self.close_pool()
                await self.create_pool()
                return True
            except Exception as e:
                logger.error(f"Failed to recreate pool: {e}")
                return False
        return True
    
    @property
    def pool(self) -> Optional[Pool]:
        """Get the connection pool."""
        return self._pool


# Global pool instance
_pool: Optional[DatabasePool] = None


def get_pool() -> DatabasePool:
    """Get the global database pool instance."""
    global _pool
    if _pool is None:
        raise RuntimeError("Database pool not initialized")
    return _pool


def init_pool(config: DatabaseConfig) -> DatabasePool:
    """Initialize and return the global database pool."""
    global _pool
    _pool = DatabasePool(config)
    return _pool

