"""Setup script for the bot (optional, for development)."""

from setuptools import setup, find_packages

setup(
    name="ivao-discord-bot",
    version="2.0.0",
    description="Modern IVAO Discord Bot",
    packages=find_packages(),
    python_requires=">=3.9",
    install_requires=[
        "discord.py==2.3.1",
        "python-dotenv==1.0.0",
        "aiomysql==0.2.0",
        "aiohttp==3.9.4",
        "colorlog==6.7.0",
    ],
)

