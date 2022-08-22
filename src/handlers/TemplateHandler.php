<?php
namespace verbb\shortcodes\handlers;

use verbb\shortcodes\Shortcodes;

use Craft;
use craft\base\ElementInterface;

use yii\base\BaseObject;
use yii\base\Event;

use Exception;
use RuntimeException;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class TemplateHandler extends BaseObject implements ShortcodeHandlerInterface
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_RENDER = 'beforeRender';


    // Properties
    // =========================================================================

    public string $template = '';
    public string $code = '';
    public array $context = [];


    // Public Methods
    // =========================================================================

    /**
     * TemplateHandler constructor.
     *
     * @param string $code The shortcode tag name
     * @param string $template The Twig template path
     */
    public function __construct(string $code, string $template)
    {
        parent::__construct([
            'code' => $code,
            'context' => [],
            'template' => $template,
        ]);
    }

    /**
     * @param ShortcodeInterface $shortcode
     * @return string
     * @throws Exception in case of failure
     * @throws RuntimeException in case of failure
     */
    public function __invoke(ShortcodeInterface $shortcode): string
    {
        $this->setContext($shortcode);

        return Craft::$app->getView()->renderTemplate($this->template, $this->context);
    }


    // Protected Methods
    // =========================================================================

    /**
     * Assembles Twig context variables for the template. It includes the
     * element variable for convenience: `entry`, `category`, etc.
     *
     * @param ShortcodeInterface $shortcode
     */
    protected function setContext(ShortcodeInterface $shortcode): void
    {
        $context = ['shortcode' => $shortcode];

        /** @var ElementInterface $element */
        $element = Craft::$app->getUrlManager()->getMatchedElement();

        if ($element) {
            $context[$element->refHandle()] = $element;
        }

        // The plugin stashes any context provided by the Twig filter, so we'll check for a stash and add it
        $shortcodeContext = Shortcodes::$plugin->getContext()->get();

        if (is_array($shortcodeContext)) {
            foreach ($shortcodeContext as $key => $value) {
                $context[$key] = $value;
            }
        }

        $this->context = $context;

        // Give modules and other plugins a chance to modify
        Event::trigger($this, static::EVENT_BEFORE_RENDER);
    }

}
