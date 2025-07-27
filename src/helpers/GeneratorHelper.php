<?php

namespace mijewe\critter\helpers;

use mijewe\critter\Critter;
use mijewe\critter\events\RegisterGeneratorsEvent;
use mijewe\critter\generators\CriticalCssCliGenerator;
use mijewe\critter\generators\CriticalCssDotComGenerator;
use mijewe\critter\generators\DummyGenerator;
use mijewe\critter\generators\GeneratorInterface;
use mijewe\critter\generators\NoGenerator;
use yii\base\Event;

class GeneratorHelper
{
    /**
     * @event RegisterGeneratorsEvent
     */
    public const EVENT_REGISTER_GENERATORS = 'registerGenerators';

    /**
     * @var array Registered generator classes
     */
    private static array $_generators = [];

    /**
     * @var bool Whether generators have been registered
     */
    private static bool $_generatorsRegistered = false;

    /**
     * Returns an array of all available generators.
     * This includes the default generators and any custom generators
     * that are registered via events.
     * Note: this returns an array of instances. If you want the class names,
     * use `getGeneratorTypes()` instead.
     */
    static function getGeneratorInstances(): array
    {
        $generators = [];
        foreach (self::getGeneratorTypes() as $generatorType) {
            if (self::isValidGenerator($generatorType)) {
                $generators[] = new $generatorType();
            }
        }
        return $generators;
    }

    /**
     * Returns an array of all available generator types.
     * This includes the default generators and any custom generators
     * that are registered via events.
     * Note: this returns an array of classes.
     * If you want the instances, use `getGeneratorInstances()` instead.
     */
    static function getGeneratorTypes(): array
    {
        if (!self::$_generatorsRegistered) {
            self::_registerGenerators();
        }

        return self::$_generators;
    }

    /**
     * Returns an array of all available generator types as <select> options.
     */
    static function getGeneratorTypesAsSelectOptions(): array
    {
        return array_map(function ($generatorType) {
            return [
                'label' => $generatorType::displayName(),
                'value' => $generatorType,
            ];
        }, self::getGeneratorTypes());
    }

    /**
     * Checks if the given generator type is a valid generator.
     */
    static function isValidGenerator(string $generatorType): bool
    {
        return is_subclass_of($generatorType, GeneratorInterface::class);
    }

    /**
     * Registers a generator class
     */
    public static function registerGenerator(string $generatorClass): void
    {
        if (self::isValidGenerator($generatorClass) && !in_array($generatorClass, self::$_generators)) {
            self::$_generators[] = $generatorClass;
        }
    }

    /**
     * Registers the default generators and fires an event to allow other plugins to register custom generators
     */
    private static function _registerGenerators(): void
    {
        // Register default generators
        $defaultGenerators = [
            NoGenerator::class,
            CriticalCssDotComGenerator::class,
            CriticalCssCliGenerator::class,
        ];

        // if developer mode, register the dummy generator
        if (Critter::getInstance()->isDeveloperMode()) {
            $defaultGenerators[] = DummyGenerator::class;
        }

        // Merge default generators with existing ones
        if (!isset(self::$_generators)) {
            self::$_generators = [];
        }

        foreach ($defaultGenerators as $generator) {
            self::registerGenerator($generator);
        }
        // Add whatever generator is currently selected to the list of generator types,
        // as it may not be in the list above if the config file is
        // set up with a custom generator.
        $currentGeneratorType = Critter::getInstance()->settings->generatorType;
        if (self::isValidGenerator($currentGeneratorType)) {
            self::registerGenerator($currentGeneratorType);
        }

        // Fire event to allow other plugins to register custom generators
        $event = new RegisterGeneratorsEvent([
            'generators' => self::$_generators
        ]);

        Event::trigger(self::class, self::EVENT_REGISTER_GENERATORS, $event);

        // Add any generators that were registered via the event
        foreach ($event->generators as $generator) {
            self::registerGenerator($generator);
        }

        self::$_generatorsRegistered = true;
    }
}
