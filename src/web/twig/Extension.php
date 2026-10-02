<?php
namespace verbb\shortcodes\web\twig;

use verbb\shortcodes\facades\ShortcodeFacade as TrackingShortcodeFacade;
use verbb\shortcodes\Shortcodes;

use craft\helpers\Html;
use craft\helpers\Template as TemplateHelper;

use Exception;

use Thunder\Shortcode\Syntax\CommonSyntax;
use Thunder\Shortcode\Syntax\Syntax;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

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
        return $this->processShortcodes((string)$markup, $options, $markup instanceof Markup);
    }

    public function shortcodesBlocksFilter($markup, $options = null): Markup
    {
        $trustedHtml = $markup instanceof Markup;
        $markup = (string)$markup;

        if ($trustedHtml) {
            $markup = $this->unwrapBlockShortcodes($markup);
        }

        return $this->processShortcodes($markup, $options, $trustedHtml);
    }

    private function processShortcodes(string $markup, $options = null, bool $trustedHtml = false): Markup
    {
        if (is_array($options) && array_key_exists('context', $options)) {
            if (is_array($options['context'])) {
                Shortcodes::$plugin->getContext()->set($options['context']);
            } else {
                throw new Exception('Shortcode context must be a key/value object in Twig');
            }
        }

        try {
            if ($trustedHtml) {
                $processed = Shortcodes::$shortcode->process($markup);
            } else {
                $syntax = $this->_getSyntax();

                if (Shortcodes::$shortcode instanceof TrackingShortcodeFacade) {
                    $processed = Shortcodes::$shortcode->processPlainText(
                        $markup,
                        fn(string $text): string => $this->_encodeLiteral($text, $syntax),
                        fn($shortcode, ?string $safeContent): string => $this->_encodeUnhandledShortcode($shortcode, $safeContent, $syntax),
                    );
                } else {
                    $processed = Shortcodes::$shortcode->process($this->_encodeLiteral($markup, $syntax));
                }
            }
        } finally {
            Shortcodes::$plugin->getContext()->clear();
        }

        return TemplateHelper::raw($processed);
    }

    private function _getSyntax(): array
    {
        $settings = Shortcodes::$plugin->getSettings();
        $usesCustomSyntax = in_array($settings->parser, [Shortcodes::PARSER_REGEX, Shortcodes::PARSER_REGULAR], true) && $settings->syntax;
        $syntaxObject = $usesCustomSyntax ? new Syntax(...$settings->syntax) : new CommonSyntax();

        return [
            $syntaxObject->getOpeningTag(),
            $syntaxObject->getClosingTag(),
            $syntaxObject->getClosingTagMarker(),
        ];
    }

    private function _encodeUnhandledShortcode($shortcode, ?string $safeContent, array $syntax): string
    {
        $shortcodeText = $shortcode->getText();
        $content = $shortcode->getContent();

        if ($content === null || $safeContent === null) {
            return $this->_encodeLiteral($shortcodeText, $syntax);
        }

        $closingPattern = '~' . preg_quote($syntax[0], '~') . '\s*' . preg_quote($syntax[2], '~') . '\s*' .
            preg_quote($shortcode->getName(), '~') . '\s*' . preg_quote($syntax[1], '~') . '$~u';

        if (preg_match($closingPattern, $shortcodeText, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return $this->_encodeLiteral($shortcodeText, $syntax);
        }

        $closingStart = mb_strlen(substr($shortcodeText, 0, $matches[0][1]), 'UTF-8');
        $contentLength = mb_strlen($content, 'UTF-8');
        $contentStart = $closingStart - $contentLength;

        if ($contentStart < 0 || mb_substr($shortcodeText, $contentStart, $contentLength, 'UTF-8') !== $content) {
            return $this->_encodeLiteral($shortcodeText, $syntax);
        }

        $prefix = mb_substr($shortcodeText, 0, $contentStart, 'UTF-8');
        $suffix = mb_substr($shortcodeText, $contentStart + $contentLength, null, 'UTF-8');

        return $this->_encodeLiteral($prefix, $syntax) . $safeContent . $this->_encodeLiteral($suffix, $syntax);
    }

    private function _encodeLiteral(string $text, array $syntax): string
    {
        $encoded = Html::encode($text);
        $replacements = [];

        foreach (array_unique([$syntax[0], $syntax[1]]) as $syntaxTag) {
            $characters = mb_str_split($syntaxTag, 1, 'UTF-8');
            $replacements[Html::encode($syntaxTag)] = implode('', array_map(
                fn(string $character): string => '&#' . mb_ord($character, 'UTF-8') . ';',
                $characters,
            ));
        }

        return strtr($encoded, $replacements);
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

        if (!(Shortcodes::$shortcode instanceof TrackingShortcodeFacade) || !Shortcodes::$shortcode->hasHandler($shortcode->getName())) {
            return false;
        }

        return method_exists($shortcode, 'getOffset') && method_exists($shortcode, 'getText') &&
            $shortcode->getOffset() === 0 && $shortcode->getText() === $content;
    }
}
