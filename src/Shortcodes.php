<?php
namespace verbb\shortcodes;

use verbb\shortcodes\base\PluginTrait;
use verbb\shortcodes\handlers\ShortcodeHandlerInterface;
use verbb\shortcodes\handlers\TemplateHandler;
use verbb\shortcodes\models\Settings;
use verbb\shortcodes\twigextensions\Extension;

use Craft;
use craft\base\Plugin;

use Exception;

use Thunder\Shortcode\Parser\RegexParser;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Parser\WordpressParser;
use Thunder\Shortcode\ShortcodeFacade;
use Thunder\Shortcode\Syntax\CommonSyntax;
use Thunder\Shortcode\Syntax\Syntax;

class Shortcodes extends Plugin
{
    // Constants
    // =========================================================================

    public const PARSER_REGEX = 'regex';
    public const PARSER_REGULAR = 'regular';
    public const PARSER_WORDPRESS = 'wordpress';


    // Properties
    // =========================================================================

    public $schemaVersion = '1.0.0';

    public static $shortcode;


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerTwigExtensions();

        $this->_initShortcodeFacade();
        $this->_initHandlers();
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    // Private Methods
    // =========================================================================

    private function _registerTwigExtensions(): void
    {
        Craft::$app->getView()->registerTwigExtension(new Extension);
    }

    /**
     * Initializes the ShortcodeFacade instance.
     *
     * @throws Exception
     */
    private function _initShortcodeFacade(): void
    {
        self::$shortcode = new ShortcodeFacade();
        $settings = $this->getSettings();

        // Syntax
        if ($settings->syntax) {
            $symbols = $settings->syntax;

            if (count($symbols) !== 5) {
                throw new Exception("Invalid Shortcode syntax. It must be an array with 5 symbols like: ['[[', ']]' , '//', '== , '\"\"']");
            }

            $syntax = new Syntax($symbols[0], $symbols[1], $symbols[2], $symbols[3], $symbols[4]);
        } else {
            $syntax = new CommonSyntax();
        }

        // Parser
        switch ($settings->parser) {
            case static::PARSER_REGEX:
                self::$shortcode->setParser(new RegexParser($syntax));
                break;

            case static::PARSER_REGULAR:
                self::$shortcode->setParser(new RegularParser($syntax));
                break;

            default:
                self::$shortcode->setParser(new WordpressParser());
                break;
        }
    }

    /**
     * Adds handlers to the `ShortcodeFacade` instance from the config
     * `map` property array. Each array value must be a valid template route
     * or a factory function which returns a callable to handle the shortcode.
     *
     * @throws Exception On an invalid configuration
     */
    private function _initHandlers(): void
    {
        $map = $this->getSettings()->map ?? [];

        foreach ($map as $code => $value) {
            $handler = class_exists($value) ? new $value : new TemplateHandler($code, $value);

            if (!in_array(ShortcodeHandlerInterface::class, class_implements(get_class($handler)))) {
                $this->_invalidConfig($code, 'Shortcode handler class must implement ' . ShortcodeHandlerInterface::class);
            }

            static::$shortcode->addHandler($code, $handler);
        }
    }

    /**
     * Throws a general Exception for invalid configuration;
     *
     * @param string $code
     * @param string $extra
     * @throws Exception
     */
    private function _invalidConfig(string $code, string $extra = ''): void
    {
        throw new Exception("Invalid shortcode configuration for `{$code}`. {$extra}");
    }
}
