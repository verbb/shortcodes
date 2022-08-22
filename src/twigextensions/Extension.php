<?php
namespace verbb\shortcodes\twigextensions;

use verbb\shortcodes\Shortcodes;

use craft\helpers\Template as TemplateHelper;

use Twig_Extension;
use Twig_SimpleFunction;
use Twig_SimpleFilter;
use Twig_Environment;

class Extension extends Twig_Extension
{
    // Public Methods
    // =========================================================================

    public function getName()
    {
        return 'Shortcodes';
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('shortcodes', [$this, 'shortcodesFilter']),
            new Twig_SimpleFilter('sc', [$this, 'shortcodesFilter']),
        ];
    }

    public function shortcodesFilter($markup, $options = null)
    {
        if (is_array($options) && array_key_exists('context', $options)) {
            if (is_array($options['context'])) {
                Shortcodes::$plugin->getContext()->set($options['context']);
            } else {
                throw new \Exception("Shortcode context must be a key/value object in Twig");
            }
        }

        $processed = Shortcodes::$shortcode->process((string) $markup);

        Shortcodes::$plugin->getContext()->clear();

        return TemplateHelper::raw($processed);
    }
}
