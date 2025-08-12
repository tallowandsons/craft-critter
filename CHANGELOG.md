# Release Notes for Critter

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
