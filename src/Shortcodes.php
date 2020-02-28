<?php
namespace samhernandez\shortcodes;

use Craft;
use craft\base\Plugin;
use samhernandez\shortcodes\handlers\ShortcodeHandlerInterface;
use samhernandez\shortcodes\handlers\TemplateHandler;
use samhernandez\shortcodes\models\Settings;
use samhernandez\shortcodes\twigextensions\ShortcodesTwigExtension;
use Thunder\Shortcode\Parser\RegexParser;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Parser\WordpressParser;
use Thunder\Shortcode\ShortcodeFacade;
use Thunder\Shortcode\Syntax\CommonSyntax;
use Thunder\Shortcode\Syntax\Syntax;

class Shortcodes extends Plugin
{
    const PARSER_REGEX = 'regex';
    const PARSER_REGULAR = 'regular';
    const PARSER_WORDPRESS = 'wordpress';

    /**
     * @var ShortcodeFacade
     */
    public static $shortcode;

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function init()
    {
        parent::init();

        $this->initShortcodeFacade();
        $this->initTwig();
        $this->initHandlers();
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Initializes the ShortcodeFacade instance.
     * @throws \Exception
     */
    protected function initShortcodeFacade()
    {
        self::$shortcode = new ShortcodeFacade();
        $settings = $this->getSettings();

        // Syntax
        if ($settings->syntax) {
            $symbols = $settings->syntax;

            if (count($symbols) !== 5) {
                throw new \Exception("Invalid Shortcode syntax. It must be an array with 5 symbols like: ['[[', ']]' , '//', '== , '\"\"']");
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
     * Registers the `shortcodes` Twig filter.
     */
    protected function initTwig()
    {
        Craft::$app->view->registerTwigExtension(new ShortcodesTwigExtension());
    }

    /**
     * Adds handlers to the `ShortcodeFacade` instance from the config
     * `map` property array. Each array value must be a valid template route
     * or a factory function which returns a callable to handle the shortcode.
     *
     * @throws \Exception On an invalid configuration
     */
    protected function initHandlers()
    {
        $map = $this->getSettings()->map ?? [];

        foreach ($map as $code => $value) {
            $handler = class_exists($value) ? new $value : new TemplateHandler($code, $value);

            if (!in_array(ShortcodeHandlerInterface::class, class_implements(get_class($handler)))) {
                $this->invalidConfig($code, 'Shortcode handler class must implement ' . ShortcodeHandlerInterface::class);
            }

            static::$shortcode->addHandler($code, $handler);
        }
    }

    /**
     * Throws a general Exception for invalid configuration;
     * @param string $code
     * @param string $extra
     * @throws \Exception
     */
    protected function invalidConfig(string $code, $extra = '')
    {
        throw new \Exception("Invalid shortcode configuration for `{$code}`. {$extra}");
    }
}
