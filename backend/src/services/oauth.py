"""OAuth2 service with retry logic and rate limiting."""

import logging
import asyncio
from datetime import datetime
from typing import Optional, Dict, Any, Union
import aiohttp
from aiohttp import ClientTimeout, ClientError

from ..config.settings import OAuthConfig
from ..database.pool import get_pool
from ..utils.exceptions import OAuthError, TokenRefreshError

logger = logging.getLogger("discord")


class OAuthService:
    """Handles IVAO OAuth2 operations with retry logic."""
    
    TOKEN_URL = "https://api.ivao.aero/v2/oauth/token"
    USER_INFO_URL = "https://api.ivao.aero/v2/users/me"
    MAX_RETRIES = 3
    RETRY_DELAY = 1.0  # seconds
    REQUEST_TIMEOUT = 10  # seconds
    
    def __init__(self, config: OAuthConfig):
        """
        Initialize OAuth service.
        
        Args:
            config: OAuth configuration
        """
        self.config = config
        self._session: Optional[aiohttp.ClientSession] = None
    
    async def _get_session(self) -> aiohttp.ClientSession:
        """Get or create HTTP session."""
        if self._session is None or self._session.closed:
            timeout = ClientTimeout(total=self.REQUEST_TIMEOUT)
            self._session = aiohttp.ClientSession(timeout=timeout)
        return self._session
    
    async def close(self) -> None:
        """Close HTTP session."""
        if self._session and not self._session.closed:
            await self._session.close()
    
    async def _refresh_token_request(
        self,
        refresh_token: str,
        retry_count: int = 0
    ) -> Dict[str, Any]:
        """
        Make a token refresh request with retry logic.
        
        Args:
            refresh_token: The refresh token to use
            retry_count: Current retry attempt
            
        Returns:
            Token response data
            
        Raises:
            TokenRefreshError: If refresh fails after retries
        """
        session = await self._get_session()
        headers = {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
        data = {
            'grant_type': 'refresh_token',
            'refresh_token': refresh_token,
            'client_id': self.config.client_id,
            'client_secret': self.config.client_secret,
        }
        
        try:
            async with session.post(self.TOKEN_URL, headers=headers, data=data) as response:
                result = await response.json()
                
                if response.status == 200 and 'access_token' in result:
                    return result
                elif 'error' in result:
                    error_type = result.get('error', 'unknown_error')
                    error_desc = result.get('error_description', 'No description')
                    
                    # Don't retry on certain errors
                    if error_type in ('invalid_grant', 'invalid_client', 'unauthorized_client'):
                        raise TokenRefreshError(f"{error_type}: {error_desc}")
                    
                    # Retry on server errors
                    if response.status >= 500 and retry_count < self.MAX_RETRIES:
                        await asyncio.sleep(self.RETRY_DELAY * (retry_count + 1))
                        return await self._refresh_token_request(refresh_token, retry_count + 1)
                    
                    raise TokenRefreshError(f"{error_type}: {error_desc}")
                else:
                    raise TokenRefreshError(f"Unexpected response: {result}")
                    
        except ClientError as e:
            if retry_count < self.MAX_RETRIES:
                await asyncio.sleep(self.RETRY_DELAY * (retry_count + 1))
                return await self._refresh_token_request(refresh_token, retry_count + 1)
            raise TokenRefreshError(f"Network error: {e}") from e
        except Exception as e:
            raise TokenRefreshError(f"Unexpected error: {e}") from e
    
    async def refresh_token(
        self,
        user_id: Optional[int] = None,
        vid: Optional[str] = None,
        revoke: bool = False
    ) -> Dict[str, Any]:
        """
        Refresh access token for a user.
        
        Args:
            user_id: Discord user ID (optional if vid provided)
            vid: IVAO VID (optional if user_id provided)
            revoke: Whether to revoke old refresh token
            
        Returns:
            User info from IVAO API
            
        Raises:
            TokenRefreshError: If refresh fails
            OAuthError: For other OAuth errors
        """
        pool = get_pool().pool
        if not pool:
            raise OAuthError("Database pool not available")
        
        if not user_id and not vid:
            raise OAuthError("Either user_id or vid must be provided")
        
        # Get refresh token from database
        async with pool.acquire() as conn:
            async with conn.cursor() as cursor:
                if user_id:
                    await cursor.execute(
                        "SELECT refresh_token FROM user_data WHERE discord_user_id = %s",
                        (user_id,)
                    )
                else:
                    await cursor.execute(
                        "SELECT refresh_token FROM user_data WHERE vid = %s",
                        (vid,)
                    )
                
                result = await cursor.fetchone()
                
                if not result or not result[0]:
                    identifier = f"user {user_id}" if user_id else f"VID {vid}"
                    raise TokenRefreshError(
                        f"No refresh token found for {identifier}. User needs to re-authenticate."
                    )
                
                refresh_token = result[0]
        
        # Refresh the token
        try:
            token_data = await self._refresh_token_request(refresh_token)
        except TokenRefreshError as e:
            identifier = f"user {user_id}" if user_id else f"VID {vid}"
            logger.warning(f"Token refresh failed for {identifier}: {e}")
            raise
        
        # Update refresh token in database
        new_refresh_token = token_data.get('refresh_token')
        if new_refresh_token:
            async with pool.acquire() as conn:
                async with conn.cursor() as cursor:
                    if user_id:
                        await cursor.execute(
                            "UPDATE user_data SET refresh_token = %s, refresh_token_date = %s WHERE discord_user_id = %s",
                            (new_refresh_token, datetime.now(), user_id)
                        )
                    else:
                        await cursor.execute(
                            "UPDATE user_data SET refresh_token = %s, refresh_token_date = %s WHERE vid = %s",
                            (new_refresh_token, datetime.now(), vid)
                        )
                    await conn.commit()
        
        # Get user info
        access_token = token_data['access_token']
        user_info = await self.get_user_info(access_token)
        
        return user_info
    
    async def get_user_info(self, access_token: str) -> Dict[str, Any]:
        """
        Get user information from IVAO API.
        
        Args:
            access_token: OAuth access token
            
        Returns:
            User information dictionary
            
        Raises:
            OAuthError: If request fails
        """
        session = await self._get_session()
        headers = {
            'Authorization': f'Bearer {access_token}'
        }
        
        try:
            async with session.get(self.USER_INFO_URL, headers=headers) as response:
                if response.status == 200:
                    return await response.json()
                else:
                    error_data = await response.json()
                    error_msg = error_data.get('error_description', f'HTTP {response.status}')
                    raise OAuthError(f"Failed to get user info: {error_msg}")
        except ClientError as e:
            raise OAuthError(f"Network error getting user info: {e}") from e
        except Exception as e:
            raise OAuthError(f"Unexpected error getting user info: {e}") from e
    
    async def get_user_info_for_discord_user(
        self,
        user_id: Optional[int] = None,
        vid: Optional[str] = None,
        revoke: bool = False
    ) -> Dict[str, Any]:
        """
        Get user info by refreshing token if needed.
        
        Args:
            user_id: Discord user ID (optional if vid provided)
            vid: IVAO VID (optional if user_id provided)
            revoke: Whether to revoke old refresh token
            
        Returns:
            User information dictionary
        """
        return await self.refresh_token(user_id=user_id, vid=vid, revoke=revoke)

