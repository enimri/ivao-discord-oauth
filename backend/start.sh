#!/bin/bash

# Start script for IVAO Discord Bot

echo "Starting IVAO Discord Bot..."

# Check Python version
PYTHON_CMD="python3.9"
if ! command -v $PYTHON_CMD &> /dev/null; then
    PYTHON_CMD="python3"
    if ! command -v $PYTHON_CMD &> /dev/null; then
        echo "Error: Python 3.9+ is required but not found!"
        echo "Please install Python 3.9 or later."
        exit 1
    fi
fi

# Verify Python version is 3.9+
PYTHON_VERSION=$($PYTHON_CMD --version 2>&1 | awk '{print $2}' | cut -d. -f1,2)
REQUIRED_VERSION="3.9"

if [ "$(printf '%s\n' "$REQUIRED_VERSION" "$PYTHON_VERSION" | sort -V | head -n1)" != "$REQUIRED_VERSION" ]; then
    echo "Error: Python 3.9+ is required, but found $PYTHON_VERSION"
    exit 1
fi

echo "Using Python: $($PYTHON_CMD --version)"

# Check if .env exists
if [ ! -f .env ]; then
    echo "Error: .env file not found!"
    echo "Please copy .env.example to .env and fill in your credentials."
    exit 1
fi

# Install/update dependencies
echo "Installing dependencies..."
$PYTHON_CMD -m pip install -r requirements.txt --quiet

# Start the bot
echo "Starting bot..."
$PYTHON_CMD -m src.bot

