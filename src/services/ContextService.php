<?php
namespace samhernandez\shortcodes\services;

use craft\base\Component;

/**
 * Provides a stash for template handler context.
 *
 * @package samhernandez\shortcodes\services
 */
class ContextService extends Component
{
    /**
     * Key/value array for Twig template context
     * @var array
     */
    protected $context = [];

    /**
     * @throws \Exception
     * @param array $context
     */
    public function set($context)
    {
        if (!is_array($context)) {
            throw new \Exception("Context must be of type Array");
        }

        $this->context = $context;
    }

    /**
     * @return array
     */
    public function get() {
        return $this->context;
    }

    public function clear() {
        $this->context = [];
    }
}
