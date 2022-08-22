<?php
namespace verbb\shortcodes\base;

use verbb\shortcodes\Shortcodes;
use verbb\shortcodes\services\Context;

use Craft;

use yii\log\Logger;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static Shortcodes $plugin;


    // Public Methods
    // =========================================================================

    public static function log($message, $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('shortcodes', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'shortcodes');
    }

    public static function error($message, $attributes = []): void
    {
        if ($attributes) {
            $message = Craft::t('shortcodes', $message, $attributes);
        }

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'shortcodes');
    }


    // Public Methods
    // =========================================================================

    public function getContext(): Context
    {
        return $this->get('context');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'context' => Context::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging(): void
    {
        BaseHelper::setFileLogging('shortcodes');
    }

}