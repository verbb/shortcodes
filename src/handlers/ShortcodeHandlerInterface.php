<?php
namespace samhernandez\shortcodes\handlers;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

/**
 * A shortcode handler class must be invokable.
 *
 * @package samhernandez\shortcodes\handlers
 */
interface ShortcodeHandlerInterface
{
    /**
     * @param ShortcodeInterface $shortcode
     * @return string
     */
    public function __invoke(ShortcodeInterface $shortcode);
}
