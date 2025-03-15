<?php

namespace honchoagency\craftcriticalcssgenerator\models;

use Craft;
use craft\base\Model;

/**
 * Css Model model
 */
class CssModel extends Model
{
    public ?string $css;

    public function __construct(?string $css = null)
    {
        $this->css = $css;
    }

    public function setCss(string $css): void
    {
        $this->css = $css;
    }

    public function getCss(): ?string
    {
        return $this->css;
    }

    public function isEmpty()
    {
        return empty($this->css);
    }

    public function stamp(string $key): void
    {
        $header = "/* Critical CSS - $key */";
        $footer = "/* generated at " . (new \DateTime())->format('Y-m-d H:i:s') . " */";
        $str = $header . PHP_EOL . $this->css . PHP_EOL . $footer;

        $this->setCss($str);
    }
}
