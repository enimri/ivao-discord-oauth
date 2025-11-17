"""Application settings and configuration management."""

import os
from typing import List, Optional
from dataclasses import dataclass, field
from dotenv import load_dotenv

from .validators import (
    validate_required,
    validate_int,
    validate_bool,
    validate_list,
    ConfigError
)


# Load environment variables
load_dotenv()


@dataclass
class DiscordConfig:
    """Discord bot configuration."""
    token: str
    debug_token: Optional[str] = None
    bot_id: int = 0
    log_channel_id: int = 0
    help_channel_id: int = 0
    bot_managers: List[int] = field(default_factory=list)
    
    @classmethod
    def from_env(cls) -> "DiscordConfig":
        """Load Discord configuration from environment variables."""
        token = validate_required("token", os.getenv("DISCORD_TOKEN"), "DISCORD_TOKEN")
        debug_token = os.getenv("DEBUG_TOKEN")
        bot_id = validate_int("bot_id", os.getenv("BOT_ID"), "BOT_ID")
        log_channel_id = validate_int("log_channel_id", os.getenv("LOGCHANNEL_ID"), "LOGCHANNEL_ID")
        help_channel_id = validate_int("help_channel_id", os.getenv("HELP_CHANNEL_ID"), "HELP_CHANNEL_ID")
        
        managers_str = os.getenv("BOTMANAGERS", "")
        bot_managers = [
            validate_int("manager_id", manager_id.strip(), f"BOTMANAGERS[{i}]")
            for i, manager_id in enumerate(managers_str.split(","))
            if manager_id.strip()
        ]
        
        return cls(
            token=token,
            debug_token=debug_token,
            bot_id=bot_id,
            log_channel_id=log_channel_id,
            help_channel_id=help_channel_id,
            bot_managers=bot_managers
        )


@dataclass
class OAuthConfig:
    """IVAO OAuth2 configuration."""
    client_id: str
    client_secret: str
    state: str
    
    @classmethod
    def from_env(cls) -> "OAuthConfig":
        """Load OAuth configuration from environment variables."""
        client_id = validate_required("client_id", os.getenv("OAUTH_CLIENT_ID"), "OAUTH_CLIENT_ID")
        client_secret = validate_required("client_secret", os.getenv("OAUTH_CLIENT_SECRET"), "OAUTH_CLIENT_SECRET")
        state = validate_required("state", os.getenv("OAUTH_STATE"), "OAUTH_STATE")
        
        return cls(
            client_id=client_id,
            client_secret=client_secret,
            state=state
        )


@dataclass
class DatabaseConfig:
    """Database configuration."""
    host: str
    port: int
    user: str
    password: str
    database: str
    min_size: int = 1
    max_size: int = 10
    pool_recycle: int = 3600
    
    @classmethod
    def from_env(cls) -> "DatabaseConfig":
        """Load database configuration from environment variables."""
        host = validate_required("host", os.getenv("HOST"), "HOST")
        port = validate_int("port", os.getenv("PORT"), "PORT", min_value=1, max_value=65535)
        user = validate_required("user", os.getenv("DBUSER"), "DBUSER")
        password = validate_required("password", os.getenv("PASSWORD"), "PASSWORD")
        database = validate_required("database", os.getenv("DATABASE"), "DATABASE")
        
        min_size = validate_int("min_size", os.getenv("DB_POOL_MIN_SIZE", "1"), "DB_POOL_MIN_SIZE", min_value=1)
        max_size = validate_int("max_size", os.getenv("DB_POOL_MAX_SIZE", "10"), "DB_POOL_MAX_SIZE", min_value=1)
        pool_recycle = validate_int("pool_recycle", os.getenv("DB_POOL_RECYCLE", "3600"), "DB_POOL_RECYCLE", min_value=60)
        
        return cls(
            host=host,
            port=port,
            user=user,
            password=password,
            database=database,
            min_size=min_size,
            max_size=max_size,
            pool_recycle=pool_recycle
        )


@dataclass
class DivisionConfig:
    """Division-specific configuration."""
    division: str
    country: str
    language: str
    icon_url: str
    enable_status_report: bool = False
    status_report_url: Optional[str] = None
    status_report_interval: int = 120
    
    # Role IDs
    div_staff: int = 0
    div_hq: int = 0
    specops: int = 0
    flightops: int = 0
    atcops: int = 0
    training: int = 0
    web: int = 0
    membership: int = 0
    event: int = 0
    pr: int = 0
    vid_verified: int = 0
    div_member: int = 0
    non_div_ivao_member: int = 0
    
    @classmethod
    def from_env(cls) -> "DivisionConfig":
        """Load division configuration from environment variables."""
        division = validate_required("division", os.getenv("DIV"), "DIV")
        country = validate_required("country", os.getenv("COUNTRY"), "COUNTRY")
        language = validate_required("language", os.getenv("LANGUAGE"), "LANGUAGE")
        icon_url = validate_required("icon_url", os.getenv("ICONURL"), "ICONURL")
        
        enable_status_report = validate_bool("enable_status_report", os.getenv("ENABLE_STATUS_REPORT", "false"), "ENABLE_STATUS_REPORT", default=False)
        status_report_url = os.getenv("STATUS_REPORT_URL", "http://localhost:8080/status_report")
        status_report_interval = validate_int("status_report_interval", os.getenv("STATUS_REPORT_INTERVAL", "120"), "STATUS_REPORT_INTERVAL", min_value=10)
        
        # Role IDs
        div_staff = validate_int("div_staff", os.getenv("DIV_STAFF"), "DIV_STAFF")
        div_hq = validate_int("div_hq", os.getenv("DIV_HQ"), "DIV_HQ")
        specops = validate_int("specops", os.getenv("SPECOPS"), "SPECOPS")
        flightops = validate_int("flightops", os.getenv("FLIGHTOPS"), "FLIGHTOPS")
        atcops = validate_int("atcops", os.getenv("ATCOPS"), "ATCOPS")
        training = validate_int("training", os.getenv("TRAINING"), "TRAINING")
        web = validate_int("web", os.getenv("WEB"), "WEB")
        membership = validate_int("membership", os.getenv("MEMBERSHIP"), "MEMBERSHIP")
        event = validate_int("event", os.getenv("EVENT"), "EVENT")
        pr = validate_int("pr", os.getenv("PR"), "PR")
        vid_verified = validate_int("vid_verified", os.getenv("VID_VERIFIED"), "VID_VERIFIED")
        div_member = validate_int("div_member", os.getenv("DIV_MEMBER"), "DIV_MEMBER")
        non_div_ivao_member = validate_int("non_div_ivao_member", os.getenv("NON_DIV_IVAO_MEMBER"), "NON_DIV_IVAO_MEMBER")
        
        return cls(
            division=division,
            country=country,
            language=language,
            icon_url=icon_url,
            enable_status_report=enable_status_report,
            status_report_url=status_report_url,
            status_report_interval=status_report_interval,
            div_staff=div_staff,
            div_hq=div_hq,
            specops=specops,
            flightops=flightops,
            atcops=atcops,
            training=training,
            web=web,
            membership=membership,
            event=event,
            pr=pr,
            vid_verified=vid_verified,
            div_member=div_member,
            non_div_ivao_member=non_div_ivao_member
        )


@dataclass
class Settings:
    """Application settings container."""
    discord: DiscordConfig
    oauth: OAuthConfig
    database: DatabaseConfig
    division: DivisionConfig
    debug: bool = False
    log_level: str = "INFO"
    
    @classmethod
    def load(cls, debug: bool = False) -> "Settings":
        """Load all settings from environment variables."""
        try:
            return cls(
                discord=DiscordConfig.from_env(),
                oauth=OAuthConfig.from_env(),
                database=DatabaseConfig.from_env(),
                division=DivisionConfig.from_env(),
                debug=debug,
                log_level=os.getenv("LOG_LEVEL", "INFO").upper()
            )
        except ConfigError as e:
            raise ConfigError(f"Configuration error: {e}") from e
    
    def is_bot_manager(self, user_id: int) -> bool:
        """Check if a user is a bot manager."""
        return user_id in self.discord.bot_managers


# Global settings instance
_settings: Optional[Settings] = None


def get_settings() -> Settings:
    """Get the global settings instance."""
    global _settings
    if _settings is None:
        raise RuntimeError("Settings not initialized. Call Settings.load() first.")
    return _settings


def init_settings(debug: bool = False) -> Settings:
    """Initialize and return the global settings instance."""
    global _settings
    _settings = Settings.load(debug=debug)
    return _settings

