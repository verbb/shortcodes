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
    public function shortcodesFilter($markup)
    {
        $processed = Shortcodes::$shortcode->process((string) $markup);

        return TemplateHelper::raw($processed);
    }
}
