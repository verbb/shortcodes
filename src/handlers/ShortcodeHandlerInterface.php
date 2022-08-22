<?php
namespace verbb\shortcodes\handlers;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * A shortcode handler class must be invokable.
 *
 * @package verbb\shortcodes\handlers
 */
interface ShortcodeHandlerInterface
{
    /**
     * @param ShortcodeInterface $shortcode
     * @param object|null $context
     * @return string
     */
    public function __invoke(ShortcodeInterface $shortcode);
}
