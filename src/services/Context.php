<?php
namespace verbb\shortcodes\services;

use craft\base\Component;

class Context extends Component
{
    // Properties
    // =========================================================================

    /**
     * Key/value array for Twig template context
     * @var array
     */
    protected $context = [];


    // Public Methods
    // =========================================================================

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
    public function get()
    {
        return $this->context;
    }

    public function clear()
    {
        $this->context = [];
    }
}
