![Banner](./docs/img/banner.png)

# Critter for Craft CMS 🐿️

**Supercharge your site's performance** with automatic critical CSS generation. Critter intelligently inlines the CSS needed for above-the-fold content, delivering improved performance and exceptional user experiences.

## 🔧 Quick Start

### 1. Install Critter

You can install Critter by searching for “Critter” in the Craft Plugin Store, or install manually using composer.
```bash
composer require tallow-and-sons/craft-critter
```

### 2. Configure Your Generator

Critter comes pre-configured with the **criticalcss.com** cloud-based generator. To get started, you'll need to configure your API key:

1. Sign up for an account at [criticalcss.com](https://criticalcss.com)
2. Get your API key from your account dashboard
3. In Craft, go to **Critter → Settings → General → Generator**
4. Enter your API key in the generator settings

ℹ️ You can also switch to a different generator or implement your own custom generator if you have specific requirements.

### 3. Celebrate 🎉

You've successfully set up Critter! Open a web page and keep your eye on the queue as Critter automatically generates and inlines the critical CSS for your entries.

## Why Choose Critter?

### Minimal-Configuration Critical CSS
Critter comes ready to use with the [criticalcss.com](https://criticalcss.com) API pre-configured. Simply add your API key and you're ready to generate critical CSS - no template changes or complicated configuration required.

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
composer require tallow-and-sons/craft-critter
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

Critter comes pre-configured with the [criticalcss.com](https://criticalcss.com) API for cloud-based critical CSS generation. This professional service requires no server dependencies and operates on a pay-per-use model.

To get started, simply configure your API key in the plugin settings under **Critter → Settings → General → Generator**. You can also adjust options for viewport dimensions and other generation parameters.

If you have specific requirements, you can implement your own generator by writing a class that extends the `BaseGenerator` and registering it with Critter.

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

Built by [Tallow &amp; Sons](https://github.com/tallow-and-sons) for the discerning developer in quiet pursuit of haste.
