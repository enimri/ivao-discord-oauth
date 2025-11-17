"""Authentication cog for user verification and token management."""

import logging
import asyncio
from typing import Optional
import discord
from discord import app_commands
from discord.ext import commands

from ..config.settings import get_settings
from ..database.pool import get_pool
from ..services.oauth import OAuthService
from ..services.auth import AuthService
from ..utils.exceptions import OAuthError, TokenRefreshError

logger = logging.getLogger("discord")


class Auth(commands.Cog):
    """Handles user authentication and verification."""
    
    def __init__(self, bot: commands.Bot):
        """
        Initialize auth cog.
        
        Args:
            bot: Bot instance
        """
        self.bot = bot
        settings = get_settings()
        
        # Initialize services
        oauth_service = OAuthService(settings.oauth)
        self.auth_service = AuthService(oauth_service)
        self.oauth_service = oauth_service
    
    @commands.Cog.listener()
    async def on_ready(self) -> None:
        """Called when cog is ready."""
        logger.info("Auth cog loaded")
    
    @commands.Cog.listener()
    async def on_member_join(self, member: discord.Member) -> None:
        """Handle new member joining."""
        logger.info(f"{member.name} ({member.id}) joined the server")
        result = await self.auth_service.verify_member(member, new_member=True)
        
        if result['success']:
            await self._apply_roles(member, result['user_info'])
            logger.info(f"Successfully verified {member.name} ({member.id})")
        else:
            error_code = result.get('error_code', 1)
            logger.warning(
                f"Failed to verify {member.name} ({member.id}): "
                f"{result.get('error_message', 'Unknown error')} (code: {error_code})"
            )
    
    @app_commands.command(name="auth", description="Manual authentication")
    async def auth(self, interaction: discord.Interaction) -> None:
        """Manual authentication command."""
        await interaction.response.defer(ephemeral=True)
        
        result = await self.auth_service.verify_member(interaction.user)
        
        if result['success']:
            await self._apply_roles(interaction.user, result['user_info'])
            await interaction.followup.send("✅ Authentication successful!", ephemeral=True)
        else:
            error_msg = result.get('error_message', 'Unknown error')
            await interaction.followup.send(f"❌ Authentication failed: {error_msg}", ephemeral=True)
    
    @app_commands.command(name="staffauth", description="Staff authentication - STAFF ONLY")
    async def staffauth(
        self,
        interaction: discord.Interaction,
        member: discord.Member
    ) -> None:
        """Staff authentication command."""
        await interaction.response.defer(ephemeral=True)
        
        # Check if user is staff
        settings = get_settings()
        guild = interaction.guild
        if not guild:
            await interaction.followup.send("This command can only be used in a server.", ephemeral=True)
            return
        
        staff_role = guild.get_role(settings.division.div_staff)
        if not staff_role or staff_role not in interaction.user.roles:
            await interaction.followup.send("❌ You don't have permission to use this command.", ephemeral=True)
            return
        
        logger.info(
            f"{interaction.user.name}/{interaction.user.id} used staffauth on "
            f"{member.name}/{member.id}"
        )
        
        result = await self.auth_service.verify_member(member)
        
        if result['success']:
            await self._apply_roles(member, result['user_info'])
            await interaction.followup.send(
                f"✅ Successfully authenticated {member.mention}",
                ephemeral=True
            )
        else:
            error_msg = result.get('error_message', 'Unknown error')
            await interaction.followup.send(
                f"❌ Failed to authenticate {member.mention}: {error_msg}",
                ephemeral=True
            )
    
    @app_commands.command(name="refreshtokens", description="Refresh tokens - STAFF ONLY")
    async def refreshtokens(
        self,
        interaction: discord.Interaction,
        member: Optional[discord.Member] = None,
        days_old: int = 10,
        all_users: bool = False
    ) -> None:
        """Refresh tokens in database."""
        await interaction.response.defer(ephemeral=True)
        
        # Check permissions
        settings = get_settings()
        guild = interaction.guild
        if not guild:
            await interaction.followup.send("This command can only be used in a server.", ephemeral=True)
            return
        
        staff_role = guild.get_role(settings.division.div_staff)
        if not staff_role or staff_role not in interaction.user.roles:
            await interaction.followup.send("❌ You don't have permission to use this command.", ephemeral=True)
            return
        
        # Build query
        pool = get_pool().pool
        if not pool:
            await interaction.followup.send("❌ Database pool not available", ephemeral=True)
            return
        
        if member:
            await interaction.followup.send(f"Refreshing token for {member.mention}...", ephemeral=True)
            
            logger.info(
                f"Refreshtokens: Searching for member '{member.name}' (ID: {member.id}, "
                f"Display: '{member.display_name}')"
            )
            
            # Try to find user by Discord ID first (convert to string since DB stores as varchar)
            sql = "SELECT discord_user_id, vid, refresh_token FROM user_data WHERE discord_user_id = %s"
            params = (str(member.id),)
            
            async with pool.acquire() as conn:
                async with conn.cursor() as cursor:
                    await cursor.execute(sql, params)
                    users = await cursor.fetchall()
            
            logger.info(f"Refreshtokens: Discord ID lookup found {len(users)} user(s)")
            
            # If not found by Discord ID, try multiple fallback methods
            if not users:
                vid = None
                
                # Method 1: Try to extract VID from display name (format: "Name | VID" or "Name | XM-SOC VID")
                if member.display_name and '|' in member.display_name:
                    try:
                        parts = member.display_name.split('|')
                        if len(parts) >= 2:
                            # Get the part after the pipe and try to find numeric VID
                            after_pipe = parts[1].strip()
                            # Split by spaces and find the first numeric value
                            for part in after_pipe.split():
                                if part.isdigit() and len(part) >= 4:  # VIDs are typically 6 digits
                                    vid = part
                                    logger.info(f"Extracted VID {vid} from nickname '{member.display_name}' for token refresh")
                                    break
                    except Exception as e:
                        logger.debug(f"Could not extract VID from nickname: {e}")
                
                # Method 2: Try looking up by Discord username
                if not users and member.name:
                    sql = "SELECT discord_user_id, vid, refresh_token FROM user_data WHERE discord_username = %s"
                    params = (member.name,)
                    async with pool.acquire() as conn:
                        async with conn.cursor() as cursor:
                            await cursor.execute(sql, params)
                            users = await cursor.fetchall()
                    
                    logger.info(f"Refreshtokens: Discord username '{member.name}' lookup found {len(users)} user(s)")
                    if users:
                        logger.info(f"Found user by Discord username '{member.name}' for token refresh")
                
                # Method 3: Try looking up by firstname and lastname (extracted from display name or name)
                if not users and member.display_name:
                    try:
                        # Try to extract first and last name from display name (format: "FirstName LastName | ...")
                        name_part = member.display_name.split('|')[0].strip()
                        name_parts = name_part.split()
                        if len(name_parts) >= 2:
                            firstname = name_parts[0]
                            lastname = ' '.join(name_parts[1:])
                            sql = """SELECT discord_user_id, vid, refresh_token FROM user_data 
                                     WHERE firstname = %s AND lastname = %s"""
                            params = (firstname, lastname)
                            async with pool.acquire() as conn:
                                async with conn.cursor() as cursor:
                                    await cursor.execute(sql, params)
                                    users = await cursor.fetchall()
                            
                            logger.info(
                                f"Refreshtokens: Name lookup '{firstname} {lastname}' found {len(users)} user(s)"
                            )
                            if users:
                                logger.info(f"Found user by name '{firstname} {lastname}' for token refresh")
                    except Exception as e:
                        logger.debug(f"Could not lookup by name: {e}")
                
                # Method 4: If we have VID, look up by VID
                if not users and vid:
                    sql = "SELECT discord_user_id, vid, refresh_token FROM user_data WHERE vid = %s"
                    params = (vid,)
                    async with pool.acquire() as conn:
                        async with conn.cursor() as cursor:
                            await cursor.execute(sql, params)
                            users = await cursor.fetchall()
                    
                    logger.info(f"Refreshtokens: VID {vid} lookup found {len(users)} user(s)")
                    if users:
                        logger.info(f"Found user by VID {vid} for token refresh")
                
                # If found by VID or username, update Discord user ID in database
                if users:
                    user_row = users[0]
                    found_vid = user_row[1] if len(user_row) > 1 else None
                    if found_vid:
                        async with pool.acquire() as conn:
                            async with conn.cursor() as cursor:
                                await cursor.execute(
                                    "UPDATE user_data SET discord_user_id = %s WHERE vid = %s",
                                    (str(member.id), found_vid)
                                )
                                await conn.commit()
                                logger.info(f"Updated discord_user_id for VID {found_vid} to {member.id}")
            
            # If still no users found, return error
            if not users:
                error_msg = (
                    f"No users found matching the criteria. "
                    f"Could not find user in database by Discord ID ({member.id}), "
                    f"Discord username ({member.name}), display name, or VID."
                )
                logger.warning(f"Refreshtokens: {error_msg}")
                await interaction.followup.send(error_msg, ephemeral=True)
                return
        elif all_users:
            sql = "SELECT discord_user_id, vid, refresh_token FROM user_data WHERE refresh_token IS NOT NULL"
            params = ()
            await interaction.followup.send("Refreshing tokens for all users with refresh tokens... This may take a while.", ephemeral=True)
            
            # Get users
            async with pool.acquire() as conn:
                async with conn.cursor() as cursor:
                    await cursor.execute(sql, params)
                    users = await cursor.fetchall()
            
            if not users:
                await interaction.followup.send("No users found matching the criteria.", ephemeral=True)
                return
        else:
            sql = """SELECT discord_user_id, vid, refresh_token FROM user_data 
                     WHERE refresh_token IS NOT NULL 
                     AND TIMESTAMPDIFF(DAY, refresh_token_date, NOW()) > %s"""
            params = (days_old,)
            await interaction.followup.send(f"Refreshing tokens older than {days_old} days...", ephemeral=True)
            
            # Get users
            async with pool.acquire() as conn:
                async with conn.cursor() as cursor:
                    await cursor.execute(sql, params)
                    users = await cursor.fetchall()
            
            if not users:
                await interaction.followup.send("No users found matching the criteria.", ephemeral=True)
                return
        
        # Refresh tokens
        total = len(users)
        successful = 0
        failed = 0
        errors = []
        
        for idx, user_row in enumerate(users, 1):
            # Handle different query result formats
            if len(user_row) == 3:
                discord_id, vid, refresh_token = user_row
            else:
                discord_id, vid = user_row
                refresh_token = None
            
            # Check if user has a refresh token
            if not refresh_token:
                failed += 1
                identifier = f"User {discord_id}" if discord_id else f"VID {vid}"
                errors.append(f"{identifier} (VID: {vid}): No refresh token found. User needs to re-authenticate.")
                logger.warning(f"Token refresh skipped for {identifier}: No refresh token")
                continue
            
            try:
                # Use VID if discord_id is None
                if discord_id:
                    await self.oauth_service.refresh_token(user_id=discord_id)
                elif vid:
                    await self.oauth_service.refresh_token(vid=vid)
                else:
                    failed += 1
                    errors.append(f"User has no Discord ID or VID")
                    continue
                successful += 1
            except (OAuthError, TokenRefreshError) as e:
                failed += 1
                identifier = f"User {discord_id}" if discord_id else f"VID {vid}"
                errors.append(f"{identifier} (VID: {vid}): {str(e)}")
                logger.warning(f"Token refresh failed for {identifier}: {e}")
            
            # Update progress
            if idx % 10 == 0 or idx == total:
                await interaction.followup.send(
                    f"Progress: {idx}/{total} processed... ✅ {successful} successful, ❌ {failed} failed",
                    ephemeral=True
                )
            
            if total > 1 and idx < total:
                await asyncio.sleep(0.5)
        
        # Final result
        result_msg = (
            f"**Token refresh completed**\n\n"
            f"✅ Successful: {successful}\n"
            f"❌ Failed: {failed}\n"
            f"📊 Total: {total}\n"
        )
        
        if errors:
            result_msg += f"\n**Errors (first 10):**\n" + "\n".join(errors[:10])
            if len(errors) > 10:
                result_msg += f"\n... and {len(errors) - 10} more errors."
        
        await interaction.followup.send(result_msg, ephemeral=True)
        logger.info(f"Token refresh completed: {successful} successful, {failed} failed out of {total} total")
    
    async def _apply_roles(self, member: discord.Member, user_info: dict) -> None:
        """Apply roles to member based on user info."""
        settings = get_settings()
        guild = member.guild
        
        # Get role objects
        staff = guild.get_role(settings.division.div_staff)
        div_hq = guild.get_role(settings.division.div_hq)
        specops = guild.get_role(settings.division.specops)
        flightops = guild.get_role(settings.division.flightops)
        atcops = guild.get_role(settings.division.atcops)
        training = guild.get_role(settings.division.training)
        web = guild.get_role(settings.division.web)
        membership = guild.get_role(settings.division.membership)
        event = guild.get_role(settings.division.event)
        pr = guild.get_role(settings.division.pr)
        vid_verified = guild.get_role(settings.division.vid_verified)
        div_member = guild.get_role(settings.division.div_member)
        non_div_ivao_member = guild.get_role(settings.division.non_div_ivao_member)
        
        first_name = user_info.get('firstName', '')
        last_name = user_info.get('lastName', '')
        vid = user_info.get('id', '')
        div = user_info.get('divisionId', '')
        name = f"{first_name} {last_name}" if first_name and last_name else member.name
        
        # Check if user is staff
        if user_info.get('isStaff'):
            staff_positions = []
            if 'userStaffPositions' in user_info and isinstance(user_info['userStaffPositions'], list):
                for position in user_info['userStaffPositions']:
                    if isinstance(position, dict) and 'id' in position:
                        staff_positions.append(position['id'])
            
            if div == settings.division.division:
                # Map staff positions to roles
                role_mapping = {
                    # Director and Assistant Director -> all staff roles
                    f"{settings.division.division}-DIR": [staff, div_hq, specops, flightops, atcops, training, web, membership, event, pr],
                    f"{settings.division.division}-ADIR": [staff, div_hq, specops, flightops, atcops, training, web, membership, event, pr],
                    # Special Operations
                    f"{settings.division.division}-SOC": [staff, specops],
                    f"{settings.division.division}-SOAC": [staff, specops],
                    f"{settings.division.division}-SOA1": [staff, specops],
                    # Flight Operations
                    f"{settings.division.division}-FOC": [staff, flightops],
                    f"{settings.division.division}-FOAC": [staff, flightops],
                    f"{settings.division.division}-FOA1": [staff, flightops],
                    # ATC Operations
                    f"{settings.division.division}-AOC": [staff, atcops],
                    f"{settings.division.division}-AOAC": [staff, atcops],
                    f"{settings.division.division}-AOA1": [staff, atcops],
                    # Training
                    f"{settings.division.division}-TC": [staff, training],
                    f"{settings.division.division}-TAC": [staff, training],
                    f"{settings.division.division}-TA1": [staff, training],
                    # Web
                    f"{settings.division.division}-WM": [staff, web],
                    f"{settings.division.division}-AWM": [staff, web],
                    f"{settings.division.division}-WMA1": [staff, web],
                    # Membership
                    f"{settings.division.division}-MC": [staff, membership],
                    f"{settings.division.division}-MAC": [staff, membership],
                    f"{settings.division.division}-MA1": [staff, membership],
                    # Event
                    f"{settings.division.division}-EC": [staff, event],
                    f"{settings.division.division}-EAC": [staff, event],
                    f"{settings.division.division}-EA1": [staff, event],
                    # Public Relations
                    f"{settings.division.division}-PRC": [staff, pr],
                    f"{settings.division.division}-PRAC": [staff, pr],
                    f"{settings.division.division}-PRA1": [staff, pr],
                }
                
                position_names = {
                    f"{settings.division.division}-DIR": "DIR",
                    f"{settings.division.division}-ADIR": "ADIR",
                    f"{settings.division.division}-SOC": "SOC",
                    f"{settings.division.division}-SOAC": "SOAC",
                    f"{settings.division.division}-SOA1": "SOA1",
                    f"{settings.division.division}-FOC": "FOC",
                    f"{settings.division.division}-FOAC": "FOAC",
                    f"{settings.division.division}-FOA1": "FOA1",
                    f"{settings.division.division}-AOC": "AOC",
                    f"{settings.division.division}-AOAC": "AOAC",
                    f"{settings.division.division}-AOA1": "AOA1",
                    f"{settings.division.division}-TC": "TC",
                    f"{settings.division.division}-TAC": "TAC",
                    f"{settings.division.division}-TA1": "TA1",
                    f"{settings.division.division}-WM": "WM",
                    f"{settings.division.division}-AWM": "AWM",
                    f"{settings.division.division}-WMA1": "WMA1",
                    f"{settings.division.division}-MC": "MC",
                    f"{settings.division.division}-MAC": "MAC",
                    f"{settings.division.division}-MA1": "MA1",
                    f"{settings.division.division}-EC": "EC",
                    f"{settings.division.division}-EAC": "EAC",
                    f"{settings.division.division}-EA1": "EA1",
                    f"{settings.division.division}-PRC": "PRC",
                    f"{settings.division.division}-PRAC": "PRAC",
                    f"{settings.division.division}-PRA1": "PRA1",
                }
                
                div_ranks = [
                    f"{settings.division.division}-DIR", f"{settings.division.division}-ADIR",
                    f"{settings.division.division}-SOC", f"{settings.division.division}-SOAC", f"{settings.division.division}-SOA1",
                    f"{settings.division.division}-FOC", f"{settings.division.division}-FOAC", f"{settings.division.division}-FOA1",
                    f"{settings.division.division}-AOC", f"{settings.division.division}-AOAC", f"{settings.division.division}-AOA1",
                    f"{settings.division.division}-TC", f"{settings.division.division}-TAC", f"{settings.division.division}-TA1",
                    f"{settings.division.division}-WM", f"{settings.division.division}-AWM", f"{settings.division.division}-WMA1",
                    f"{settings.division.division}-MC", f"{settings.division.division}-MAC", f"{settings.division.division}-MA1",
                    f"{settings.division.division}-EC", f"{settings.division.division}-EAC", f"{settings.division.division}-EA1",
                    f"{settings.division.division}-PRC", f"{settings.division.division}-PRAC", f"{settings.division.division}-PRA1",
                ]
                
                matching_positions = [pos for pos in staff_positions if pos in div_ranks]
                role_names = []
                
                for position in matching_positions:
                    roles = role_mapping.get(position, [])
                    for role in roles:
                        if role and role not in member.roles:
                            try:
                                await member.add_roles(role, reason="IVAO staff authentication")
                            except discord.Forbidden:
                                logger.error(f"Missing permissions to add role {role.name} to {member.name}")
                    
                    position_name = position_names.get(position)
                    if position_name and position_name not in role_names:
                        role_names.append(position_name)
                        # Add base member and verified roles
                        if div_member and div_member not in member.roles:
                            try:
                                await member.add_roles(div_member, reason="IVAO authentication")
                            except discord.Forbidden:
                                logger.error(f"Missing permissions to add DIV_MEMBER role to {member.name}")
                        if vid_verified and vid_verified not in member.roles:
                            try:
                                await member.add_roles(vid_verified, reason="IVAO authentication")
                            except discord.Forbidden:
                                logger.error(f"Missing permissions to add VID_VERIFIED role to {member.name}")
                
                # Update nickname with staff position
                if role_names:
                    role_str = "/".join(role_names)
                    nickname = f"{name} | {settings.division.division}-{role_str}"
                    if len(nickname) > 32:
                        nickname = f"{name} | {settings.division.division} Staff"
                else:
                    nickname = f"{name} | {settings.division.division} Staff"
                
                try:
                    await member.edit(nick=nickname)
                except discord.Forbidden:
                    logger.error(f"Missing permissions to edit nickname for {member.name}")
                except Exception as e:
                    logger.warning(f"Could not set nickname '{nickname}': {e}")
                    # Try shorter version
                    try:
                        await member.edit(nick=f"{member.name} | {settings.division.division} Staff")
                    except:
                        pass
            else:
                # Staff from other division
                nickname = f"{name} | {vid}" if len(f"{name} | {vid}") < 32 else f"{member.name} | {vid}"
                try:
                    await member.edit(nick=nickname)
                except discord.Forbidden:
                    logger.error(f"Missing permissions to edit nickname for {member.name}")
                
                if vid_verified and vid_verified not in member.roles:
                    try:
                        await member.add_roles(vid_verified, reason="IVAO authentication")
                    except discord.Forbidden:
                        logger.error(f"Missing permissions to add VID_VERIFIED role to {member.name}")
                if non_div_ivao_member and non_div_ivao_member not in member.roles:
                    try:
                        await member.add_roles(non_div_ivao_member, reason="IVAO authentication")
                    except discord.Forbidden:
                        logger.error(f"Missing permissions to add NON_DIV_IVAO_MEMBER role to {member.name}")
        else:
            # Not staff - regular member
            nickname = f"{name} | {vid}" if len(f"{name} | {vid}") < 32 else f"{member.name} | {vid}"
            try:
                await member.edit(nick=nickname)
            except discord.Forbidden:
                logger.error(f"Missing permissions to edit nickname for {member.name}")
            
            if div == settings.division.division:
                if div_member and div_member not in member.roles:
                    try:
                        await member.add_roles(div_member, reason="IVAO authentication")
                    except discord.Forbidden:
                        logger.error(f"Missing permissions to add DIV_MEMBER role to {member.name}")
            else:
                if non_div_ivao_member and non_div_ivao_member not in member.roles:
                    try:
                        await member.add_roles(non_div_ivao_member, reason="IVAO authentication")
                    except discord.Forbidden:
                        logger.error(f"Missing permissions to add NON_DIV_IVAO_MEMBER role to {member.name}")
            
            if vid_verified and vid_verified not in member.roles:
                try:
                    await member.add_roles(vid_verified, reason="IVAO authentication")
                except discord.Forbidden:
                    logger.error(f"Missing permissions to add VID_VERIFIED role to {member.name}")


async def setup(bot: commands.Bot) -> None:
    """Setup function for the cog."""
    await bot.add_cog(Auth(bot))

