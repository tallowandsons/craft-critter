# Custom Generators

The Critical CSS Generator plugin supports custom generators that can be registered by other plugins or modules. This allows you to extend the plugin with your own critical CSS generation methods.

## Creating a Custom Generator

To create a custom generator, you need to implement the `GeneratorInterface`:

```php
<?php

namespace yournamespace\generators;

use mijewe\craftcriticalcssgenerator\generators\GeneratorInterface;
use mijewe\craftcriticalcssgenerator\models\GeneratorResponse;
use mijewe\craftcriticalcssgenerator\models\UrlModel;

class MyCustomGenerator implements GeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(UrlModel $url): GeneratorResponse
    {
        // Your generation logic here
        $response = new GeneratorResponse();
        $response->setSuccess(true);
        $response->setCss('/* your critical css */');
        return $response;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'My Custom Generator';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        // Return HTML for generator-specific settings
        return null;
    }
}
```

## Registering a Custom Generator

### Method 1: Using Events (Recommended)

The recommended way to register custom generators is by listening to the `RegisterGeneratorsEvent`:

```php
<?php

use craft\events\Event;
use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\events\RegisterGeneratorsEvent;
use mijewe\craftcriticalcssgenerator\helpers\GeneratorHelper;
use yournamespace\generators\MyCustomGenerator;

Event::on(
    GeneratorHelper::class,
    GeneratorHelper::EVENT_REGISTER_GENERATORS,
    function(RegisterGeneratorsEvent $event) {
        $event->generators[] = MyCustomGenerator::class;
    }
);
```

### Method 2: Using the Static Method

You can also register generators directly using the static method:

```php
<?php

use mijewe\craftcriticalcssgenerator\Critical;
use yournamespace\generators\MyCustomGenerator;

Critical::registerGenerator(MyCustomGenerator::class);
```

## Best Practices

1. **Implement all interface methods**: Ensure your generator implements all required methods from `GeneratorInterface`.

2. **Handle errors gracefully**: Return a `GeneratorResponse` with `setSuccess(false)` and use `setException()` to provide error details.

3. **Provide settings**: If your generator has configurable options, implement `getSettingsHtml()` to return a settings form.

4. **Use descriptive names**: Provide a clear `displayName()` that identifies your generator.

5. **Register early**: Register your generators during plugin initialization to ensure they're available when needed.

## Example Plugin Integration

Here's how a plugin might register a custom generator:

```php
<?php

namespace yournamespace;

use craft\base\Plugin;
use craft\events\Event;
use mijewe\craftcriticalcssgenerator\events\RegisterGeneratorsEvent;
use mijewe\craftcriticalcssgenerator\helpers\GeneratorHelper;
use yournamespace\generators\MyCustomGenerator;

class YourPlugin extends Plugin
{
    public function init(): void
    {
        parent::init();

        // Register custom generator
        Event::on(
            GeneratorHelper::class,
            GeneratorHelper::EVENT_REGISTER_GENERATORS,
            function(RegisterGeneratorsEvent $event) {
                $event->generators[] = MyCustomGenerator::class;
            }
        );
    }
}
```

This ensures your custom generator will be available in the Critical CSS Generator plugin's settings and can be selected as the active generator.
