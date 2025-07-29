# Critter for Craft CMS 🦔

**Supercharge your site's performance** with automatic critical CSS generation. Critter intelligently inlines the CSS needed for above-the-fold content, delivering improved performance and exceptional user experiences.

## 🔧 Quick Start

### 1. Install Critter

You can install Critter by searching for “Critter” in the Craft Plugin Store, or install manually using composer.
```bash
composer require mijewe/craft-critter
```

### 2. Choose Your Generator

Over in **Critter → Settings → General → Generator**, select your preferred critical CSS generation method:

**🌐 Cloud-Based (criticalcss.com)**
- Professional API service
- No server dependencies
- Pay-per-use pricing

ℹ️ You can also implement your own custom generator if you have specific requirements.

## 🦔 Why Choose Critter?

### Minimal-Configuration Critical CSS
Automatically generate critical CSS using the [criticalcss.com](https://criticalcss.com) API - no template changes or complicated configuration required.

### Flexible Generation Modes
Generate a unique critical CSS file for each individual Entry (Entry Mode) or a shared critical CSS file for all entries in a Section (Section Mode).

### Robust & Reliable
Critical CSS generation jobs are queued and processed in the background, ensuring efficient resource usage. Mutex locks prevent duplicate API requests and help avoid rate limiting, while failed jobs are automatically retried using an exponential backoff strategy for maximum reliability.

### ⚡ Blitz Integration
Critter plays nicely with the [Blitz](https://putyourlightson.com/plugins/blitz) static caching plugin. Automatically clear, expire, and refresh the Blitz cache when a page's critical CSS changes.

### Advanced Configuration
Fine-tune every aspect: per-section settings, custom tag attributes, query parameter handling, viewport dimensions, and base URL overrides for staging environments.

## Requirements

This plugin requires Craft CMS 5.5.0 or later, and PHP 8.2 or later.

## Installation

To install the plugin, search for “Critter” in the Craft Plugin Store, or install manually using composer.

```bash
composer require mijewe/craft-critter
```

## ⚙️ Configuration

### 🔄 Auto Render Mode

#### Automatic Rendering (Default)
Critter automatically detects when critical CSS is needed and handles generation and rendering seamlessly. Perfect for most use cases.

#### Manual Rendering
For advanced control, disable Auto Render and call the render method manually:

```twig
<head>
    {{ craft.critter.render() }}
    <!-- Your other head content -->
</head>
```

### Generator Setup

Generators are responsible for creating the critical CSS. Critter comes with built-in support for cloud-based generation, and you can also implement your own.

- The [criticalcss.com](https://criticalcss.com) API, for cloud-based critical CSS generation.
- You can also implement your own generator by writing a class that extends the `BaseGenerator` and registering it with Critter.

The cloud-based generator can be configured in the plugin settings under Critter → Settings → General → Generator, with options for viewport dimensions, API keys, and more.

### Cache Configuration

Since critical CSS is generated in a queue, pages may have been cached with outdated or no critical CSS.

The cache integrations allow you to configure how Critter interacts with your caching layer.

Critter comes with built-in support for the [Blitz](https://putyourlightson.com/plugins/blitz) static caching plugin, which allows you to automatically clear, expire, or refresh the Blitz cache when critical CSS changes.

You can also implement your own cache driver by writing a class that extends the `BaseCache` and registering it with Critter.

### Per-Section Configuration

Over in Settings → Sections, you can configure how critical CSS is generated for each section of your site.

Each section can be configured to generate either unique critical CSS for each entry (Entry mode) or shared critical CSS for all entries in the section (Section mode).

This allows you to optimise performance based on the layout consistency of your entries, as it might be computationally (and financially) expensive to generate unique critical CSS for every single entry on a large site.

Over in Critter → Sections, you can also configure which entry should be used as the representative entry for each section. If this is left blank, the first entry to be visited by a user will be used as the representative entry for that section.

## Extending

### Bring Your Own Generator

You can implement your own critical CSS generator by writing a class that extends the `BaseGenerator` and registering it with Critter.

``` php
class PinkGenerator extends BaseGenerator
{
    protected function getCriticalCss(UrlModel $urlModel): GeneratorResponse
    {
        $css = '* { color: pink; }';

        return (new GeneratorResponse())
            ->setSuccess(true)
            ->setCss(new CssModel($css));
    }
}
```

Then register your generator using the `RegisterGeneratorsEvent` event

```php
Event::on(
    GeneratorHelper::class,
    GeneratorHelper::EVENT_REGISTER_GENERATORS,
    function(RegisterGeneratorsEvent $event) {
        $event->generators[] = PinkGenerator::class;
    }
);
```

### Bring Your Own Cache Driver

You can implement your own cache driver by writing a class that extends the `BaseCache` and registering it with Critter.

```php
class MyCustomStaticCache extends BaseCache
{
    public function resolveCache(UrlModel|array $urlModels): void
    {
        // Custom logic to clear the cache for the provided URLs
        AnotherStaticCachingPlugin::getInstance()->clearCacheForUrls($urlModels);
    }
}
```

Then register your cache driver using the `RegisterCacheEvent` event

```php
Event::on(
    CacheHelper::class,
    CacheHelper::EVENT_REGISTER_CACHES,
    function(RegisterCachesEvent $event) {
        $event->caches[] = MyCustomStaticCache::class;
    }
);
```

## License

This plugin requires a commercial license purchasable through the Craft Plugin Store.

## Credits

Created by [Michael Westwood](https://github.com/mijewe)
