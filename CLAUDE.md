# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository contains a FreshRSS extension called "TikTok" that filters TikTok RSS items and replaces them with clean embedded video players.

## Architecture

- **Extension Structure**: This is a FreshRSS extension following the standard extension pattern
- **Main Extension Class**: `TikTok_Extension` extends `Minz_Extension`
- **Hook System**: Uses FreshRSS's hook system via `entry_before_display` hook
- **Core Functionality**: Processes RSS entries containing TikTok URLs and replaces content with iframe embeds

## Key Files

- `xExtension-TikTok/extension.php`: Main extension logic that processes TikTok URLs and creates iframe embeds
- `xExtension-TikTok/metadata.json`: Extension metadata including name, description, author, and version

## Development

This is a simple PHP extension with no build process, testing framework, or dependencies. Changes can be made directly to the PHP files.

The extension works by:
1. Registering a hook on `entry_before_display`
2. Checking if RSS entries contain TikTok URLs
3. Extracting video IDs using regex pattern matching
4. Replacing entry content with TikTok iframe embeds

## Extension Installation

This extension should be placed in the FreshRSS extensions directory following the `xExtension-{Name}` naming convention.