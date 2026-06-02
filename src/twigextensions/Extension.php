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
            new TwigFilter('shortcodes_blocks', [$this, 'shortcodesBlocksFilter']),
            new TwigFilter('sc', [$this, 'shortcodesFilter']),
        ];
    }

    public function shortcodesFilter($markup, $options = null): Markup
    {
        return $this->processShortcodes((string)$markup, $options);
    }

    public function shortcodesBlocksFilter($markup, $options = null): Markup
    {
        return $this->processShortcodes($this->unwrapBlockShortcodes((string)$markup), $options);
    }

    private function processShortcodes(string $markup, $options = null): Markup
    {
        if (is_array($options) && array_key_exists('context', $options)) {
            if (is_array($options['context'])) {
                Shortcodes::$plugin->getContext()->set($options['context']);
            } else {
                throw new Exception('Shortcode context must be a key/value object in Twig');
            }
        }

        try {
            $processed = Shortcodes::$shortcode->process($markup);
        } finally {
            Shortcodes::$plugin->getContext()->clear();
        }

        return TemplateHelper::raw($processed);
    }

    private function unwrapBlockShortcodes(string $markup): string
    {
        return preg_replace_callback('/<p\b[^>]*>(.*?)<\/p>/is', function($matches) {
            $content = $this->normalizeParagraphContent($matches[1]);

            if ($this->isSoloShortcode($content)) {
                return $content;
            }

            return $matches[0];
        }, $markup) ?? $markup;
    }

    private function normalizeParagraphContent(string $content): string
    {
        $edgeWhitespace = '(?:\s|&nbsp;|&#160;|&#xA0;|<br\s*\/?>)';
        $content = preg_replace('/^' . $edgeWhitespace . '+/i', '', $content) ?? $content;
        $content = preg_replace('/' . $edgeWhitespace . '+$/i', '', $content) ?? $content;

        return trim($content);
    }

    private function isSoloShortcode(string $content): bool
    {
        if ($content === '') {
            return false;
        }

        $shortcodes = Shortcodes::$shortcode->parse($content);

        if (count($shortcodes) !== 1) {
            return false;
        }

        $shortcode = reset($shortcodes);
        $map = Shortcodes::$plugin->getSettings()->map ?? [];

        if (!array_key_exists($shortcode->getName(), $map)) {
            return false;
        }

        return method_exists($shortcode, 'getOffset') && method_exists($shortcode, 'getText') &&
            $shortcode->getOffset() === 0 && $shortcode->getText() === $content;
    }
}
