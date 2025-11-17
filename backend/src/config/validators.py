"""Configuration validators."""

from typing import Any
import os


class ConfigError(Exception):
    """Raised when configuration is invalid."""
    pass


def validate_required(key: str, value: Any, env_key: str) -> Any:
    """Validate that a required configuration value is present."""
    if value is None:
        raise ConfigError(f"Required environment variable '{env_key}' is not set")
    return value


def validate_int(key: str, value: Any, env_key: str, min_value: int = None, max_value: int = None) -> int:
    """Validate and convert an integer configuration value."""
    if value is None:
        raise ConfigError(f"Required environment variable '{env_key}' is not set")
    try:
        int_value = int(value)
        if min_value is not None and int_value < min_value:
            raise ConfigError(f"'{env_key}' must be >= {min_value}")
        if max_value is not None and int_value > max_value:
            raise ConfigError(f"'{env_key}' must be <= {max_value}")
        return int_value
    except ValueError:
        raise ConfigError(f"'{env_key}' must be a valid integer")


def validate_bool(key: str, value: Any, env_key: str, default: bool = False) -> bool:
    """Validate and convert a boolean configuration value."""
    if value is None:
        return default
    if isinstance(value, bool):
        return value
    if isinstance(value, str):
        return value.lower() in ("true", "1", "yes", "on")
    return bool(value)


def validate_list(key: str, value: Any, env_key: str, separator: str = ",") -> list:
    """Validate and convert a comma-separated list."""
    if value is None:
        return []
    if isinstance(value, list):
        return value
    if isinstance(value, str):
        return [item.strip() for item in value.split(separator) if item.strip()]
    return []

