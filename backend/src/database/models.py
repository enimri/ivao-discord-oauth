"""Database models and data structures."""

from dataclasses import dataclass
from typing import Optional
from datetime import datetime


@dataclass
class UserData:
    """User data from database."""
    id: int
    vid: Optional[int]
    discord_user_id: int
    discord_username: str
    firstname: Optional[str]
    lastname: Optional[str]
    refresh_token: Optional[str]
    refresh_token_date: Optional[datetime]
    verified: bool
    is_banned: bool
    
    @property
    def full_name(self) -> str:
        """Get user's full name."""
        if self.firstname and self.lastname:
            return f"{self.firstname} {self.lastname}"
        return self.discord_username
    
    @property
    def has_refresh_token(self) -> bool:
        """Check if user has a refresh token."""
        return self.refresh_token is not None and self.refresh_token.strip() != ""

