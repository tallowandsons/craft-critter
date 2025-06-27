<?php

namespace mijewe\craftcriticalcssgenerator\helpers;

use mijewe\craftcriticalcssgenerator\Critical;
use mijewe\craftcriticalcssgenerator\generators\CriticalCssCliGenerator;
use mijewe\craftcriticalcssgenerator\generators\CriticalCssDotComGenerator;
use mijewe\craftcriticalcssgenerator\generators\GeneratorInterface;

class GeneratorHelper
{

    /**
     * Returns an array of all available generator types.
     * This includes the default generators and any custom generators
     * that are registered in the config file.
     */
    static function getGeneratorTypes(): array
    {
        $generatorTypes = [
            CriticalCssDotComGenerator::class,
            CriticalCssCliGenerator::class,
        ];

        // add whatever generator is currently selected to the list of generator types,
        // as it may not be in the list above if the config file is
        // set up with a custom generator.
        $currentGeneratorType = Critical::getInstance()->settings->generatorType;
        if (self::isValidGenerator($currentGeneratorType)) {
            $generatorTypes = array_unique(array_merge(
                $generatorTypes,
                [$currentGeneratorType]
            ), SORT_REGULAR);
        }

        return $generatorTypes;
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
}
