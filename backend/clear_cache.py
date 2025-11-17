#!/usr/bin/env python3
"""
Cache clearing utility for Python
Clears all __pycache__ directories and .pyc files
"""

import os
import shutil
import sys
from pathlib import Path

def clear_pycache(directory=None):
    """Clear all __pycache__ directories and .pyc files"""
    if directory is None:
        directory = Path(__file__).parent
    
    directory = Path(directory)
    
    if not directory.exists():
        print(f"Directory not found: {directory}")
        return False
    
    cleared_count = 0
    
    # Find all __pycache__ directories
    for pycache_dir in directory.rglob('__pycache__'):
        if pycache_dir.is_dir():
            try:
                # Remove all .pyc files
                for pyc_file in pycache_dir.glob('*.pyc'):
                    pyc_file.unlink()
                    cleared_count += 1
                    print(f"Removed: {pyc_file}")
                
                # Remove the __pycache__ directory
                pycache_dir.rmdir()
                cleared_count += 1
                print(f"Removed: {pycache_dir}")
            except Exception as e:
                print(f"Error removing {pycache_dir}: {e}")
    
    # Find any stray .pyc files
    for pyc_file in directory.rglob('*.pyc'):
        try:
            pyc_file.unlink()
            cleared_count += 1
            print(f"Removed: {pyc_file}")
        except Exception as e:
            print(f"Error removing {pyc_file}: {e}")
    
    if cleared_count > 0:
        print(f"\n✓ Cleared {cleared_count} cache items")
        return True
    else:
        print("\nℹ No cache files found")
        return False

if __name__ == '__main__':
    target_dir = sys.argv[1] if len(sys.argv) > 1 else None
    success = clear_pycache(target_dir)
    sys.exit(0 if success else 1)

