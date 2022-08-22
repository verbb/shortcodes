<?php
namespace verbb\shortcodes\handlers;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

interface ShortcodeHandlerInterface
{
    // Public Methods
    // =========================================================================

    public function __invoke(ShortcodeInterface $shortcode): string;
}
