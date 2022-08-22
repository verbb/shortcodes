<?php
namespace verbb\shortcodes\services;

use craft\base\Component;

class Context extends Component
{
    // Properties
    // =========================================================================

    /**
     * Key/value array for Twig template context
     *
     * @var array
     */
    protected $context = [];


    // Public Methods
    // =========================================================================

    public function set(array $context): void
    {
        $this->context = $context;
    }

    public function get(): array
    {
        return $this->context;
    }

    public function clear(): void
    {
        $this->context = [];
    }
}
