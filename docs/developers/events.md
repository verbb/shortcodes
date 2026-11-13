# Events

Shortcodes provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Shortcodes’ behaviour.

## Template Handler Events

### The `beforeRender` Event

The event that is triggered before a shortcode mapped to a Twig template is rendered.

It's a plain `yii\base\Event` whose `sender` is the `TemplateHandler` for the shortcode. The handler's `code` property holds the shortcode name, `template` holds the template path, and `context` holds the variables the template will receive. These include `shortcode`, the matched element for the current route (such as `entry`) and any context passed to the `shortcodes` filter. Change `$event->sender->context` to add or replace template variables, or `$event->sender->template` to render a different template. The event can't stop the shortcode from rendering. Shortcodes mapped to a PHP class don't trigger it.

```php
use verbb\shortcodes\handlers\TemplateHandler;
use yii\base\Event;

Event::on(TemplateHandler::class, TemplateHandler::EVENT_BEFORE_RENDER, function(Event $event) {
    /** @var TemplateHandler $handler */
    $handler = $event->sender;

    if ($handler->code === 'button') {
        $handler->context['buttonClass'] = 'btn btn-primary';
    }
});
```
