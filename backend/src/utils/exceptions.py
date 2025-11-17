"""Custom exceptions for the bot."""


class BotError(Exception):
    """Base exception for all bot-related errors."""
    pass


class ConfigError(BotError):
    """Raised when there's a configuration error."""
    pass


class DatabaseError(BotError):
    """Raised when there's a database error."""
    pass


class OAuthError(BotError):
    """Raised when there's an OAuth2 error."""
    pass


class TokenRefreshError(OAuthError):
    """Raised when token refresh fails."""
    pass


class UserNotFoundError(BotError):
    """Raised when a user is not found in the database."""
    pass

