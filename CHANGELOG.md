# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.2.0] - 2026-03-21

### Added
- **Pinterest**: New `PinterestProvider` using Pinterest API v5 (`POST /v5/pins`).
  Supports text/link pins and image URL pins. Requires `board_id` + `access_token`.
  Note: Pinterest requires publicly accessible image URLs — local paths are not supported.

### Fixed
- **Twitter (CRITICAL)**: Migrated media upload from deprecated `upload.twitter.com/1.1/media/upload.json`
  (closed 9 June 2025) to `api.twitter.com/2/media/upload`. Image uploads were broken.
- **Twitter**: `uploadMedia()` no longer silently swallows exceptions.
  Failures now propagate to `TwitterProvider` which logs and handles them correctly.
- **Instagram**: Replaced blind `sleep(1)` with proper container status polling.
  Provider now polls `status_code` up to 10 times (2s interval) before publishing.
  Raises `ProviderException` on `ERROR`/`EXPIRED` status or timeout.
- **LinkedIn (CRITICAL)**: Migrated from legacy `POST /v2/ugcPosts` (`com.linkedin.ugc.ShareContent`)
  to `POST https://api.linkedin.com/rest/posts` (Community Management API).
  Old endpoint was marked as Legacy in June 2023.
- **DI**: `SocialPostExtension` now always sets all container parameters (even when a provider
  is disabled), preventing potential compile errors in apps that disable providers.
- **Tests**: Fixed pre-existing PHPUnit 11 incompatibilities — `final` keyword removed from all
  `*Client` classes to allow mocking, `withConsecutive()` replaced (removed in PHPUnit 11),
  incorrect test expectations corrected.

### Changed
- **Facebook, Instagram, WhatsApp**: Updated default Graph API version from `v20.0` to `v22.0`.
- **DI**: `SocialPostExtension::configureProviders()` refactored from 100+ line if/else chains
  to a `PROVIDER_DEFAULTS` map — simpler, DRY, easier to extend.

### Deprecated
- **WhatsApp**: `WhatsAppProvider` is deprecated and will be removed in v4.0.
  WhatsApp Business Cloud API does not support posting to public Channels;
  it only sends template messages to opted-in users.

## [3.0.0] - 2026-01-16

### Added
- Complete rewrite for PHP 8.4 and Symfony 7.4
- No external SDK dependencies (all HTTP clients built-in)
- Twitter API v2 support with OAuth 1.0a
- Facebook Graph API v20+ support
- LinkedIn API v2 support
- Telegram Bot API support
- Instagram Graph API with two-step container publishing
- Discord Webhooks support
- WhatsApp Channel API support (BETA)
- Async publishing via Symfony Messenger
- `MessageBuilder` with fluent interface
- `PublishResultCollection` with rich query methods
- Event system: `BeforePublishEvent`, `AfterPublishEvent`, `PublishFailedEvent`
- Threads API support
- Full PHP 8.4 readonly properties, named arguments throughout

[Unreleased]: https://github.com/janwebdev/symfony-social-post-bundle/compare/v3.2.0...HEAD
[3.2.0]: https://github.com/janwebdev/symfony-social-post-bundle/compare/v3.0.0...v3.2.0
[3.0.0]: https://github.com/janwebdev/symfony-social-post-bundle/releases/tag/v3.0.0
