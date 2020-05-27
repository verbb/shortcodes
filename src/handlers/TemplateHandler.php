<?php

namespace samhernandez\shortcodes\handlers;

use Craft;
use craft\base\ElementInterface;
use Exception;
use RuntimeException;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Twig_Error_Loader;
use yii\base\BaseObject;
use yii\base\Event;
use samhernandez\shortcodes\Shortcodes;

class TemplateHandler extends BaseObject implements ShortcodeHandlerInterface
{
    const EVENT_BEFORE_RENDER = 'beforeRender';

    public $template;
    public $code;
    public $context;

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
            'template' => $template
        ]);
    }

    /**
     * Assembles Twig context variables for the template. It includes the
     * element variable for convenience: `entry`, `category`, etc.
     *
     * @param ShortcodeInterface $shortcode
     */
    protected function setContext(ShortcodeInterface $shortcode)
    {
        $context = ['shortcode' => $shortcode];

        /** @var ElementInterface $element */
        $element = Craft::$app->urlManager->getMatchedElement();
        if ($element) {
            $context[$element->refHandle()] = $element;
        }

        // The plugin stashes any context provided by the Twig filter
        // so we'll check for a stash and add it
        $shortcodeContext = Shortcodes::getInstance()->context->get();
        if (is_array($shortcodeContext)) {
            foreach($shortcodeContext as $key => $value) {
                $context[$key] = $value;
            }
        }

        $this->context = $context;

        // Give modules and other plugins a chance to modify
        Event::trigger($this, static::EVENT_BEFORE_RENDER);
    }

    /**
     * @param ShortcodeInterface $shortcode
     * @return string
     * @throws Twig_Error_Loader if the template doesn't exist
     * @throws Exception in case of failure
     * @throws RuntimeException in case of failure
     */
    public function __invoke(ShortcodeInterface $shortcode)
    {
        $this->setContext($shortcode);
        $rendered = Craft::$app->view->renderTemplate($this->template, $this->context);

        return $rendered;
    }
}
