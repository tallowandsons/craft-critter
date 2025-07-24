# Testing

Run tests for the Craft Critter plugin using Pest:
```
ddev php vendor/bin/pest plugins/craft-critter/tests/pest --test-directory=plugins/craft-critter/tests/pest
```

This command will execute all tests located in the `plugins/craft-critter/tests/pest` directory.

## Known Issues

- **Deprecation Warning**: Tests may show a deprecation warning about `preg_match()` passing null parameters. This is a known issue with Yii2/Craft CMS framework compatibility with PHP 8.1+ and does not affect test functionality.

## Test Coverage

The current test suite includes comprehensive coverage of:
- CSSController actionExpire method parameter validation
- All functional paths (expire all, by entry, by section)
- Edge cases and error handling
- Service integration
