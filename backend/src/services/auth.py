"""Authentication service."""

import logging
from typing import Optional, Dict, Any
import discord

from ..database.pool import get_pool
from ..database.models import UserData
from ..services.oauth import OAuthService
from ..utils.exceptions import UserNotFoundError, OAuthError

logger = logging.getLogger("discord")


class AuthService:
    """Handles user authentication and verification."""
    
    def __init__(self, oauth_service: OAuthService):
        """
        Initialize auth service.
        
        Args:
            oauth_service: OAuth service instance
        """
        self.oauth = oauth_service
    
    async def get_user_data(self, discord_user_id: int) -> Optional[UserData]:
        """
        Get user data from database.
        
        Args:
            discord_user_id: Discord user ID
            
        Returns:
            UserData if found, None otherwise
        """
        pool = get_pool().pool
        if not pool:
            return None
        
        async with pool.acquire() as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(
                    """SELECT id, vid, discord_user_id, discord_username, firstname, lastname,
                       refresh_token, refresh_token_date, verified, is_banned
                       FROM user_data WHERE discord_user_id = %s""",
                    (discord_user_id,)
                )
                result = await cursor.fetchone()
                
                if not result:
                    return None
                
                return UserData(
                    id=result[0],
                    vid=result[1],
                    discord_user_id=result[2],
                    discord_username=result[3],
                    firstname=result[4],
                    lastname=result[5],
                    refresh_token=result[6],
                    refresh_token_date=result[7],
                    verified=bool(result[8]),
                    is_banned=bool(result[9])
                )
    
    async def get_user_data_by_vid(self, vid: str) -> Optional[UserData]:
        """
        Get user data from database by VID.
        
        Args:
            vid: IVAO VID
            
        Returns:
            UserData if found, None otherwise
        """
        pool = get_pool().pool
        if not pool:
            return None
        
        async with pool.acquire() as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(
                    """SELECT id, vid, discord_user_id, discord_username, firstname, lastname,
                       refresh_token, refresh_token_date, verified, is_banned
                       FROM user_data WHERE vid = %s""",
                    (vid,)
                )
                result = await cursor.fetchone()
                
                if not result:
                    return None
                
                return UserData(
                    id=result[0],
                    vid=result[1],
                    discord_user_id=result[2],
                    discord_username=result[3],
                    firstname=result[4],
                    lastname=result[5],
                    refresh_token=result[6],
                    refresh_token_date=result[7],
                    verified=bool(result[8]),
                    is_banned=bool(result[9])
                )
    
    async def update_discord_user_id(self, vid: str, discord_user_id: int) -> bool:
        """
        Update Discord user ID for a user by VID.
        
        Args:
            vid: IVAO VID
            discord_user_id: New Discord user ID
            
        Returns:
            True if update was successful, False otherwise
        """
        pool = get_pool().pool
        if not pool:
            return False
        
        async with pool.acquire() as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(
                    "UPDATE user_data SET discord_user_id = %s WHERE vid = %s",
                    (discord_user_id, vid)
                )
                await conn.commit()
                return cursor.rowcount > 0
    
    async def verify_member(
        self,
        member: discord.Member,
        new_member: bool = False
    ) -> Dict[str, Any]:
        """
        Verify a Discord member.
        
        Args:
            member: Discord member to verify
            new_member: Whether this is a new member joining
            
        Returns:
            Dictionary with verification result:
            - success: bool
            - user_info: Optional[Dict] - IVAO user info if successful
            - error_code: Optional[int] - Error code if failed
            - error_message: Optional[str] - Error message if failed
        """
        # Get user data from database
        user_data = await self.get_user_data(member.id)
        
        # If not found by Discord ID, try to extract VID from nickname and look up by VID
        if not user_data:
            # Try to extract VID from nickname (format: "Name | VID")
            vid = None
            if member.display_name and '|' in member.display_name:
                try:
                    parts = member.display_name.split('|')
                    if len(parts) == 2:
                        potential_vid = parts[1].strip()
                        # Check if it looks like a VID (numeric)
                        if potential_vid.isdigit():
                            vid = potential_vid
                            logger.info(f"Extracted VID {vid} from nickname '{member.display_name}'")
                except Exception as e:
                    logger.debug(f"Could not extract VID from nickname: {e}")
            
            if vid:
                # Try to find user by VID
                user_data = await self.get_user_data_by_vid(vid)
                
                if user_data and user_data.has_refresh_token:
                    # Found user by VID with refresh token - update their Discord user ID
                    logger.info(
                        f"User {member.name} ({member.id}) found by VID {vid}, "
                        f"updating discord_user_id from {user_data.discord_user_id} to {member.id}"
                    )
                    await self.update_discord_user_id(vid, member.id)
                    # Update the discord_user_id in the user_data object
                    user_data.discord_user_id = str(member.id)
                elif user_data:
                    # Found user by VID but no refresh token
                    return {
                        'success': False,
                        'error_code': 2,
                        'error_message': 'User found in database but no refresh token available. User needs to re-authenticate.'
                    }
                else:
                    return {
                        'success': False,
                        'error_code': 2,
                        'error_message': 'User not found in database'
                    }
            else:
                return {
                    'success': False,
                    'error_code': 2,
                    'error_message': 'User not found in database. Could not extract VID from nickname.'
                }
        
        if user_data.is_banned:
            return {
                'success': False,
                'error_code': 3,
                'error_message': 'User is banned'
            }
        
        # Get user info from IVAO using refresh token
        try:
            # Use refresh token from database to get user info
            # Use VID if Discord ID was just updated or doesn't match
            if user_data.vid and (not user_data.discord_user_id or user_data.discord_user_id != str(member.id)):
                user_info = await self.oauth.get_user_info_for_discord_user(vid=user_data.vid)
            else:
                user_info = await self.oauth.get_user_info_for_discord_user(user_id=member.id)
            
            # Ensure we always have first/last name data by falling back to DB values
            first_name = user_info.get('firstName') or user_data.firstname
            last_name = user_info.get('lastName') or user_data.lastname
            if first_name:
                user_info['firstName'] = first_name
            if last_name:
                user_info['lastName'] = last_name
            
            # Persist latest name info in database
            await self._update_user_names(member.id, first_name, last_name)
            
            # Update discord username in database
            await self._update_discord_username(member.id, member.name)
            
            # Mark as verified
            await self._mark_verified(member.id)
            
            return {
                'success': True,
                'user_info': user_info,
                'user_data': user_data
            }
            
        except OAuthError as e:
            logger.warning(f"OAuth error verifying {member.name} ({member.id}): {e}")
            return {
                'success': False,
                'error_code': 4,
                'error_message': str(e)
            }
        except Exception as e:
            logger.error(f"Unexpected error verifying {member.name} ({member.id}): {e}")
            return {
                'success': False,
                'error_code': 1,
                'error_message': f'Unexpected error: {e}'
            }
    
    async def _update_discord_username(self, user_id: int, username: str) -> None:
        """Update Discord username in database."""
        pool = get_pool().pool
        if not pool:
            return
        
        async with pool.acquire() as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(
                    "UPDATE user_data SET discord_username = %s WHERE discord_user_id = %s",
                    (username, user_id)
                )
                await conn.commit()
    
    async def _mark_verified(self, user_id: int) -> None:
        """Mark user as verified in database."""
        pool = get_pool().pool
        if not pool:
            return
        
        async with pool.acquire() as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(
                    "UPDATE user_data SET verified = 1 WHERE discord_user_id = %s",
                    (user_id,)
                )
                await conn.commit()
    
    async def _update_user_names(self, user_id: int, first_name: Optional[str], last_name: Optional[str]) -> None:
        """Update first and last name in database if provided."""
        if not first_name and not last_name:
            return
        
        pool = get_pool().pool
        if not pool:
            return
        
        async with pool.acquire() as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(
                    """
                    UPDATE user_data
                    SET firstname = COALESCE(%s, firstname),
                        lastname = COALESCE(%s, lastname)
                    WHERE discord_user_id = %s
                    """,
                    (first_name, last_name, user_id)
                )
                await conn.commit()

