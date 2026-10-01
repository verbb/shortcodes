<?php
namespace verbb\shortcodes\parsers;

use Thunder\Shortcode\Parser\ParserInterface;
use Thunder\Shortcode\Shortcode\ParsedShortcode;
use Thunder\Shortcode\Shortcode\ParsedShortcodeInterface;
use Thunder\Shortcode\Shortcode\Shortcode;
use Thunder\Shortcode\Syntax\SyntaxInterface;

/**
 * Filters parsed shortcodes whose syntax tokens occur inside HTML markup while preserving the original source text.
 */
class HtmlAwareParser implements ParserInterface
{
    // Properties
    // =========================================================================

    private ParserInterface $parser;
    private SyntaxInterface $syntax;


    // Public Methods
    // =========================================================================

    public function __construct(ParserInterface $parser, SyntaxInterface $syntax)
    {
        $this->parser = $parser;
        $this->syntax = $syntax;
    }

    public function parse($text): array
    {
        $text = (string)$text;

        if (!str_contains($text, '<')) {
            return $this->parser->parse($text);
        }

        $htmlMarkupRanges = $this->_htmlMarkupRanges($text);

        if (!$htmlMarkupRanges) {
            return $this->parser->parse($text);
        }

        $maskedText = $this->_maskMarkupSyntax($text, $htmlMarkupRanges);
        $shortcodes = [];

        foreach ($this->parser->parse($maskedText) as $shortcode) {
            if ($maskedText !== $text) {
                $shortcode = $this->_restoreShortcode($shortcode, $text);

                if ($shortcode === null) {
                    continue;
                }
            }

            if (!$this->_shortcodeSyntaxIntersectsMarkup($shortcode, $htmlMarkupRanges)) {
                $shortcodes[] = $shortcode;
            }
        }

        return $shortcodes;
    }


    // Private Methods
    // =========================================================================

    private function _shortcodeSyntaxIntersectsMarkup(ParsedShortcodeInterface $shortcode, array $htmlMarkupRanges): bool
    {
        $shortcodeText = $shortcode->getText();
        $shortcodeOffset = (int)$shortcode->getOffset();
        $shortcodeLength = mb_strlen($shortcodeText, 'UTF-8');
        $content = $shortcode->getContent();

        if ($content === null) {
            $openingSyntaxEnd = $shortcodeLength;
            $closingSyntaxStart = null;
        } else {
            $closingSyntaxStart = mb_strrpos($shortcodeText, $this->syntax->getOpeningTag(), 0, 'UTF-8');

            if ($closingSyntaxStart === false) {
                return true;
            }

            $openingSyntaxEnd = $closingSyntaxStart - mb_strlen($content, 'UTF-8');

            if ($openingSyntaxEnd < 0) {
                return true;
            }
        }

        if ($this->_rangeIntersectsMarkup($shortcodeOffset, $shortcodeOffset + $openingSyntaxEnd, $htmlMarkupRanges)) {
            return true;
        }

        if ($closingSyntaxStart === null) {
            return false;
        }

        return $this->_rangeIntersectsMarkup(
            $shortcodeOffset + $closingSyntaxStart,
            $shortcodeOffset + $shortcodeLength,
            $htmlMarkupRanges,
        );
    }

    private function _restoreShortcode(ParsedShortcodeInterface $shortcode, string $text): ?ParsedShortcodeInterface
    {
        $shortcodeOffset = (int)$shortcode->getOffset();
        $shortcodeText = $shortcode->getText();
        $originalText = mb_substr($text, $shortcodeOffset, mb_strlen($shortcodeText, 'UTF-8'), 'UTF-8');
        $content = $shortcode->getContent();
        $originalContent = null;

        if ($content !== null) {
            $closingSyntaxStart = mb_strrpos($shortcodeText, $this->syntax->getOpeningTag(), 0, 'UTF-8');

            if ($closingSyntaxStart === false) {
                return null;
            }

            $contentLength = mb_strlen($content, 'UTF-8');
            $contentStart = $closingSyntaxStart - $contentLength;

            if ($contentStart < 0) {
                return null;
            }

            $originalContent = mb_substr($originalText, $contentStart, $contentLength, 'UTF-8');
        }

        return new ParsedShortcode(new Shortcode(
            $shortcode->getName(),
            $shortcode->getParameters(),
            $originalContent,
            $shortcode->getBbCode(),
        ), $originalText, $shortcodeOffset);
    }

    private function _maskMarkupSyntax(string $text, array $htmlMarkupRanges): string
    {
        $openingTag = $this->syntax->getOpeningTag();
        $closingTag = $this->syntax->getClosingTag();
        $maskCharacter = $this->_maskCharacter($openingTag . $closingTag);
        $characters = mb_str_split($text, 1, 'UTF-8');

        foreach (array_unique([$openingTag, $closingTag]) as $syntaxTag) {
            $syntaxLength = mb_strlen($syntaxTag, 'UTF-8');
            $offset = 0;

            while (($syntaxStart = mb_strpos($text, $syntaxTag, $offset, 'UTF-8')) !== false) {
                $syntaxEnd = $syntaxStart + $syntaxLength;

                if ($this->_rangeIntersectsMarkup($syntaxStart, $syntaxEnd, $htmlMarkupRanges)) {
                    for ($i = $syntaxStart; $i < $syntaxEnd; $i++) {
                        $characters[$i] = $maskCharacter;
                    }
                }

                $offset = $syntaxEnd;
            }
        }

        return implode('', $characters);
    }

    private function _maskCharacter(string $syntax): string
    {
        for ($codepoint = 0xE000; $codepoint <= 0xF8FF; $codepoint++) {
            $character = mb_chr($codepoint, 'UTF-8');

            if (!str_contains($syntax, $character)) {
                return $character;
            }
        }

        return "\0";
    }

    private function _rangeIntersectsMarkup(int $start, int $end, array $htmlMarkupRanges): bool
    {
        foreach ($htmlMarkupRanges as [$htmlStart, $htmlEnd]) {
            if ($htmlStart >= $end) {
                return false;
            }

            if ($start < $htmlEnd && $end > $htmlStart) {
                return true;
            }
        }

        return false;
    }

    private function _htmlMarkupRanges(string $text): array
    {
        $ranges = [];
        $offset = 0;
        $length = strlen($text);
        $characterOffset = 0;
        $lastByteOffset = 0;

        while ($offset < $length) {
            $htmlStart = strpos($text, '<', $offset);

            if ($htmlStart === false) {
                break;
            }

            if (!$this->_isHtmlMarkupStart($text, $htmlStart)) {
                $offset = $htmlStart + 1;
                continue;
            }

            $htmlEnd = $this->_htmlMarkupEnd($text, $htmlStart);
            $characterStart = $characterOffset + mb_strlen(substr($text, $lastByteOffset, $htmlStart - $lastByteOffset), 'UTF-8');
            $characterEnd = $characterStart + mb_strlen(substr($text, $htmlStart, $htmlEnd - $htmlStart), 'UTF-8');
            $ranges[] = [$characterStart, $characterEnd];
            $offset = $htmlEnd;
            $characterOffset = $characterEnd;
            $lastByteOffset = $htmlEnd;
        }

        return $ranges;
    }

    private function _isHtmlMarkupStart(string $text, int $offset): bool
    {
        if (!isset($text[$offset + 1])) {
            return false;
        }

        return preg_match('/[A-Za-z!\/?]/', $text[$offset + 1]) === 1;
    }

    private function _htmlMarkupEnd(string $text, int $offset): int
    {
        $length = strlen($text);

        if (substr($text, $offset, 4) === '<!--') {
            $commentEnd = strpos($text, '-->', $offset + 4);

            return $commentEnd === false ? $length : $commentEnd + 3;
        }

        if (substr($text, $offset, 9) === '<![CDATA[') {
            $cdataEnd = strpos($text, ']]>', $offset + 9);

            return $cdataEnd === false ? $length : $cdataEnd + 3;
        }

        $quote = null;

        for ($i = $offset + 1; $i < $length; $i++) {
            $character = $text[$i];

            if ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
            } elseif ($character === '>') {
                return $i + 1;
            }
        }

        return $length;
    }
}
