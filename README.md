# Critter

A powerful Craft CMS plugin for automatically generating and managing critical CSS to improve website performance by eliminating render-blocking CSS.

## Features

### 🚀 **Automatic Critical CSS Generation**
- Generate critical CSS using [criticalcss.com](https://criticalcss.com) API
- CLI-based generation support
- Configurable viewport dimensions (width/height)
- Automatic rendering in page templates

### 🎯 **Flexible Generation Modes**
- **URL Mode**: Generate unique critical CSS for each individual URL
- **Section Mode**: Generate shared critical CSS for all pages in a section

### 🛡️ **Robust & Reliable**
- **Mutex-based locking** prevents duplicate API requests for the same domain
- **Intelligent retry system** with exponential backoff for transient failures
- **Queue-based processing** with configurable retry attempts and delays
- **Comprehensive error handling** and logging

### 🔧 **Advanced Configuration**
- Per-section configuration options
- Custom style tag attributes
- Query parameter handling for unique URLs
- Base URL override for staging/development environments
- Blitz cache integration for optimal performance

### 🎨 **User-Friendly Interface**
- Clean Control Panel interface
- Permission-based access control
- Read-only mode for non-admin users
- Intuitive section-based configuration

## Requirements

This plugin requires Craft CMS 5.5.0 or later, and PHP 8.2 or later.

For API-based generation, you'll need a [criticalcss.com](https://criticalcss.com) API key.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project's Control Panel and search for "Critter". Then press "Install".

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require mijewe/craft-critter

# tell Craft to install the plugin
./craft plugin/install critter
```

## Configuration

### Basic Setup

1. **Configure Generator Settings**:
   ```php
   // config/critter.php
   return [
       'generatorType' => \mijewe\craftcriticalcssgenerator\generators\CriticalCssDotComGenerator::class,
       'generatorSettings' => [
           'apiKey' => '$CRITICALCSS_API_KEY',
           'width' => 1400,
           'height' => 1080,
           'maxAttempts' => 10,
           'attemptDelay' => 2,
       ],
   ];
   ```

2. **Set Environment Variables**:
   ```bash
   # .env
   CRITICALCSS_API_KEY="your-api-key-here"
   ```

### Advanced Configuration

```php
// config/critter.php
return [
    // Automatic rendering
    'autoRenderEnabled' => true,

    // Default generation mode
    'defaultMode' => 'section', // 'url' or 'section'

    // Style tag attributes
    'styleTagAttributes' => [
        ['key' => 'data-critical', 'value' => 'true'],
    ],

    // Retry settings
    'maxRetries' => 3,
    'retryBaseDelay' => 30,

    // Mutex timeout (seconds)
    'mutexTimeout' => 30,

    // Cache integration (with Blitz)
    'cacheType' => \mijewe\craftcriticalcssgenerator\drivers\caches\BlitzCache::class,
    'cacheBehaviour' => 'refreshUrls',

    // Base URL override (useful for staging)
    'baseUrlOverride' => '$STAGING_URL',
];
```

## Usage

### Template Integration

The plugin automatically renders critical CSS when `autoRenderEnabled` is true. For manual control:

```twig
{# Render critical CSS for current page #}
{{ craft.critter.render() }}

{# In your layout head #}
<head>
    {{ craft.critter.render() }}
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
```

### Generation Modes

#### URL Mode
Generates unique critical CSS for each page URL:
- Best for sites with varied page layouts
- Higher storage requirements
- Most accurate critical CSS per page

#### Section Mode
Generates shared critical CSS for all pages in a section:
- Best for sites with consistent layouts within sections
- Lower storage requirements
- Good balance of performance and efficiency

### Queue Jobs

Critical CSS generation happens asynchronously via Craft's queue system:

```bash
# Process queue manually
./craft queue/run

# Check queue status
./craft queue/info
```

### Console Commands

```bash
# Clear mutex locks (if stuck)
./craft critter/mutex/clear-all

# Generate for specific URL
./craft critter/generate https://example.com/page
```

## API Integration

### criticalcss.com Integration

The plugin integrates seamlessly with criticalcss.com:

1. **Sign up** at [criticalcss.com](https://criticalcss.com)
2. **Get your API key** from your account dashboard
3. **Configure** the API key in your environment variables
4. **Set viewport dimensions** for optimal critical CSS generation

### Custom Generators

Implement the `GeneratorInterface` to create custom generators:

```php
use mijewe\craftcriticalcssgenerator\generators\GeneratorInterface;

class CustomGenerator implements GeneratorInterface
{
    public function generate(UrlModel $url): GeneratorResponse
    {
        // Your custom generation logic
    }
}
```

## Performance Features

### Intelligent Caching
- **Mutex locking** prevents duplicate generation requests
- **Domain-level locking** for criticalcss.com API efficiency
- **Request result caching** avoids redundant API calls

### Retry Mechanism
- **Configurable retries** for transient failures
- **Exponential backoff** prevents API overwhelming
- **Detailed logging** for debugging and monitoring

### Blitz Integration
When used with Blitz caching:
- **Automatic cache invalidation** when critical CSS changes
- **Cache warming** support for optimal performance
- **Selective URL refreshing** based on generation mode

## Permissions

The plugin provides granular permissions:

- **View Critter**: Access to plugin sections
- **Edit Critter**: Modify plugin settings
- **Manage Sections**: Configure section-specific settings

## Troubleshooting

### Common Issues

**Critical CSS not generating?**
- Check your criticalcss.com API key
- Verify queue is processing (`./craft queue/run`)
- Check plugin logs for errors

**Mutex lock errors?**
- Clear stuck locks: `./craft critter/mutex/clear-all`
- Adjust `mutexTimeout` setting if needed

**Performance issues?**
- Enable Blitz cache integration
- Use Section mode for consistent layouts
- Optimize viewport dimensions

### Debug Mode

Enable debug logging in your plugin configuration.

### Log Files

Check these log files for debugging:
- `storage/logs/critter.log`
- `storage/logs/queue.log`
- `storage/logs/web.log`

## Development

### Local Development

```bash
# Install dependencies
composer install

# Run code style checks
composer check-cs

# Fix code style issues
composer fix-cs

# Run static analysis
composer phpstan
```

## Support

- **Issues**: Create issues for bug reports and feature requests
- **Email**: dev@honcho.agency
- **Documentation**: Check the plugin documentation for detailed usage

## License

Proprietary license. See LICENSE.md for details.

## Credits

Developed by [mijewe](https://github.com/mijewe) / [Honcho Agency](https://honcho.agency)

---

**Improve your website's performance with Critter!** 🚀
