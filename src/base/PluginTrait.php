<?php
namespace verbb\shortcodes\base;

use verbb\shortcodes\Shortcodes;
use verbb\shortcodes\services\Context;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?Shortcodes $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('shortcodes');

        return [
            'components' => [
                'context' => Context::class,
            ],
        ];
    }


    // Public Methods
    // =========================================================================

    public function getContext(): Context
    {
        return $this->get('context');
    }

}