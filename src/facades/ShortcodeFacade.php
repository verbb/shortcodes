<?php
namespace verbb\shortcodes\facades;

use Thunder\Shortcode\Event\ReplaceShortcodesEvent;
use Thunder\Shortcode\Events;
use Thunder\Shortcode\ShortcodeFacade as BaseShortcodeFacade;

class ShortcodeFacade extends BaseShortcodeFacade
{
    // Properties
    // =========================================================================

    private array $_eventHandlers = [];

    private array $_handlerNames = [];

    private array $_plainTextContexts = [];

    private int $_plainTextSuspensions = 0;


    // Public Methods
    // =========================================================================

    public function __construct()
    {
        parent::__construct();

        parent::addEventHandler(Events::FILTER_SHORTCODES, function($event): void {
            $this->_dispatchEvent(Events::FILTER_SHORTCODES, $event);
        });
        parent::addEventHandler(Events::REPLACE_SHORTCODES, function(ReplaceShortcodesEvent $event): void {
            $this->_dispatchReplaceEvent($event);
        });
    }

    public function addHandler($name, $handler)
    {
        parent::addHandler($name, $handler);

        $this->_handlerNames[(string)$name] = true;

        return $this;
    }

    public function addHandlerAlias($alias, $name)
    {
        parent::addHandlerAlias($alias, $name);

        $this->_handlerNames[(string)$alias] = true;

        return $this;
    }

    public function addEventHandler($name, $handler)
    {
        $this->_eventHandlers[$name][] = $handler;

        return $this;
    }

    public function hasHandler(string $name): bool
    {
        return isset($this->_handlerNames[$name]);
    }

    public function process($text)
    {
        if ($this->_plainTextContexts === []) {
            return parent::process($text);
        }

        $this->_plainTextSuspensions++;

        try {
            return parent::process($text);
        } finally {
            $this->_plainTextSuspensions--;
        }
    }

    public function processPlainText(string $text, callable $encodeLiteral, callable $encodeUnhandledShortcode): string
    {
        $this->_plainTextContexts[] = [
            'children' => [],
            'encodeLiteral' => $encodeLiteral,
            'encodeUnhandledShortcode' => $encodeUnhandledShortcode,
        ];

        try {
            return parent::process($text);
        } finally {
            array_pop($this->_plainTextContexts);
        }
    }


    // Private Methods
    // =========================================================================

    private function _dispatchEvent(string $name, object $event): void
    {
        foreach ($this->_eventHandlers[$name] ?? [] as $handler) {
            $handler($event);
        }
    }

    private function _dispatchReplaceEvent(ReplaceShortcodesEvent $event): void
    {
        $this->_dispatchEvent(Events::REPLACE_SHORTCODES, $event);

        if ($this->_plainTextContexts === [] || $this->_plainTextSuspensions > 0) {
            return;
        }

        $contextIndex = array_key_last($this->_plainTextContexts);
        $context = &$this->_plainTextContexts[$contextIndex];
        $safeMarkup = $this->_buildSafeMarkup($event, $context);
        $parent = $event->getShortcode();

        if ($parent === null) {
            $event->setResult($safeMarkup);
            return;
        }

        $context['children'][$this->_shortcodeKey($parent)][] = $safeMarkup;
    }

    private function _buildSafeMarkup(ReplaceShortcodesEvent $event, array &$context): string
    {
        if ($event->hasResult()) {
            return (string)$event->getResult();
        }

        $text = $event->getText();
        $replacements = $event->getReplacements() ?? [];
        usort($replacements, fn($a, $b): int => $a->getOffset() <=> $b->getOffset());
        $markup = '';
        $offset = 0;

        foreach ($replacements as $replacement) {
            $replacementOffset = (int)$replacement->getOffset();
            $replacementText = $replacement->getText();
            $markup .= ($context['encodeLiteral'])(mb_substr($text, $offset, $replacementOffset - $offset, 'UTF-8'));
            $safeContent = $this->_takeSafeContent($replacement, $context);

            if ($this->hasHandler($replacement->getName())) {
                $markup .= (string)$replacement->getReplacement();
            } else {
                $markup .= ($context['encodeUnhandledShortcode'])($replacement, $safeContent);
            }

            $offset = $replacementOffset + mb_strlen($replacementText, 'UTF-8');
        }

        return $markup . ($context['encodeLiteral'])(mb_substr($text, $offset, null, 'UTF-8'));
    }

    private function _takeSafeContent($shortcode, array &$context): ?string
    {
        $key = $this->_shortcodeKey($shortcode);

        if (empty($context['children'][$key])) {
            return null;
        }

        $safeContent = array_shift($context['children'][$key]);

        if ($context['children'][$key] === []) {
            unset($context['children'][$key]);
        }

        return $safeContent;
    }

    private function _shortcodeKey($shortcode): string
    {
        $text = method_exists($shortcode, 'getShortcodeText') ? $shortcode->getShortcodeText() : $shortcode->getText();

        return $shortcode->getOffset() . "\0" . $shortcode->getName() . "\0" . $text;
    }
}
