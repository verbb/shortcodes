<?php
namespace verbb\shortcodes\twigextensions;

use verbb\shortcodes\Shortcodes;

use craft\helpers\Template as TemplateHelper;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

use Exception;

class Extension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Shortcodes';
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('shortcodes', [$this, 'shortcodesFilter']),
            new TwigFilter('sc', [$this, 'shortcodesFilter']),
        ];
    }

    public function shortcodesFilter($markup, $options = null): Markup
    {
        if (is_array($options) && array_key_exists('context', $options)) {
            if (is_array($options['context'])) {
                Shortcodes::$plugin->getContext()->set($options['context']);
            } else {
                throw new Exception('Shortcode context must be a key/value object in Twig');
            }
        }

        $processed = Shortcodes::$shortcode->process((string)$markup);

        Shortcodes::$plugin->getContext()->clear();

        return TemplateHelper::raw($processed);
    }
}
