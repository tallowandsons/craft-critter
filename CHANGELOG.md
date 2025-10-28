# Release Notes for Critter

## 1.1.0 - 2025-10-28

### Added

- Added new `excludePatterns` setting, to allow admins to configure URL patterns for Critter to ignore. Fixes [#61].

[#61]: https://github.com/tallowandsons/craft-critter/issues/61

## 1.0.6 - 2025-09-18

### Added

- Added settings to add basic auth credentials to API requests - useful for staging environments behind basic auth.

## 1.0.5 - 2025-08-12

### Changed
- Lowered Craft CMS requirement to ^5.0.0.
- Overhauled logging: dedicated Monolog target and standardized `Critter::info/error` usage.
- Updated support email to support@tallowandsons.com.

### Fixed
- Improved error handling and retry logic for criticalcss.com API: `HTTP_SOCKET_HANG_UP` and `WORKER_TIMEOUT` are now retryable.
- More consistent job start and generation logging.

## 1.0.4 - 2025-08-07

### Changed
- Updated CLI generator display name to "Local CLI Generator" for clarity.
- Improved documentation for CLI generator setup, emphasizing that it is only recommended for local development.

## 1.0.0 - 2025-08-06
- Initial release
