<?php
namespace samhernandez\shortcodes\twigextensions;

use craft\helpers\Template as TemplateHelper;
use samhernandez\shortcodes\Shortcodes;
use Twig_Markup;

class ShortcodesTwigExtension extends \Twig_Extension
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Shortcodes';
    }

    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('shortcodes', [$this, 'shortcodesFilter']),
            new \Twig_SimpleFilter('sc', [$this, 'shortcodesFilter']), // alias
        ];
    }

    /**
     * Handles the `shortcodes` filter.
     * @param Twig_Markup|string $markup
     * @return Twig_Markup
     */
    public function shortcodesFilter($markup, $options = null)
    {
        if (is_array($options) && array_key_exists('context', $options)) {
            if (is_array($options['context'])) {
                Shortcodes::getInstance()->context->set($options['context']);
            } else {
                throw new \Exception("Shortcode context must be a key/value object in Twig");
            }
        }

        $processed = Shortcodes::$shortcode->process((string) $markup);

        Shortcodes::getInstance()->context->clear();

        return TemplateHelper::raw($processed);
    }
}
