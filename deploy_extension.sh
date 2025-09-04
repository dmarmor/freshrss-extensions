#!/bin/bash

# Deploy RSS-Bridge extension to FreshRSS Docker volume
# Run this from the project root directory

SOURCE_DIR="xExtension-RssBridge"
TARGET_DIR="../docker/volumes/freshrss/extensions/xExtension-RssBridge"

echo "Deploying RSS-Bridge extension..."
echo "Source: $SOURCE_DIR"
echo "Target: $TARGET_DIR"

# Check if source directory exists
if [ ! -d "$SOURCE_DIR" ]; then
    echo "Error: Source directory $SOURCE_DIR not found!"
    exit 1
fi

# Remove existing target directory if it exists
if [ -d "$TARGET_DIR" ]; then
    echo "Removing existing extension..."
    rm -rf "$TARGET_DIR"
fi

# Create target directory structure if needed
mkdir -p "$(dirname "$TARGET_DIR")"

# Copy the extension
echo "Copying extension files..."
cp -r "$SOURCE_DIR" "$TARGET_DIR"

echo "Extension deployed successfully!"
echo "You may need to restart FreshRSS or reload the extension settings."