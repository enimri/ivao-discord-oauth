"""Logging configuration."""

import logging
import sys
import os
from pathlib import Path
from typing import Optional

import colorlog


class PyNaClFilter(logging.Filter):
    """Filter to suppress PyNaCl voice support warnings."""
    def filter(self, record: logging.LogRecord) -> bool:
        """Filter out PyNaCl-related warnings."""
        message = record.getMessage()
        # Suppress PyNaCl voice warnings as they're not relevant for auth bots
        if "PyNaCl" in message and "voice" in message.lower():
            return False
        return True


def setup_logging(
    log_level: str = "INFO",
    log_file: Optional[str] = None,
    log_dir: Optional[str] = None
) -> logging.Logger:
    """
    Set up logging with both file and console handlers.
    
    Args:
        log_level: Logging level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
        log_file: Name of the log file (default: discord.log)
        log_dir: Directory for log files (default: current directory)
    
    Returns:
        Configured logger instance
    """
    logger = logging.getLogger("discord")
    logger.setLevel(getattr(logging, log_level.upper(), logging.INFO))
    logger.handlers.clear()  # Remove any existing handlers
    
    # Also filter discord.client logger (where PyNaCl warning originates)
    client_logger = logging.getLogger("discord.client")
    client_logger.setLevel(getattr(logging, log_level.upper(), logging.INFO))
    
    # Create formatters
    file_formatter = logging.Formatter(
        '[%(asctime)s] %(levelname)s [%(filename)s.%(funcName)s:%(lineno)d] %(message)s',
        datefmt='%a, %d %b %Y %H:%M:%S'
    )
    
    console_formatter = colorlog.ColoredFormatter(
        '%(log_color)s [%(asctime)s] %(levelname)s [%(filename)s.%(funcName)s:%(lineno)d] %(message)s',
        datefmt='%a, %d %b %Y %H:%M:%S',
        log_colors={
            'DEBUG': 'cyan',
            'INFO': 'green',
            'WARNING': 'yellow',
            'ERROR': 'red',
            'CRITICAL': 'red,bg_white',
        }
    )
    
    # Create filter to suppress PyNaCl warnings
    nacl_filter = PyNaClFilter()
    
    # Console handler
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(console_formatter)
    console_handler.addFilter(nacl_filter)
    logger.addHandler(console_handler)
    
    # File handler
    if log_dir:
        log_path = Path(log_dir)
        log_path.mkdir(parents=True, exist_ok=True)
    else:
        log_path = Path(".")
    
    log_file = log_file or "discord.log"
    file_path = log_path / log_file
    
    # Create log file if it doesn't exist
    if not file_path.exists():
        file_path.touch()
    
    file_handler = logging.FileHandler(file_path)
    file_handler.setFormatter(file_formatter)
    file_handler.addFilter(nacl_filter)
    logger.addHandler(file_handler)
    
    return logger

