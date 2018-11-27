# Craft CMS Shortcodes

**For Craft CMS 3**

The Craft 2 version is still available [here](https://github.com/samhernandez/craftcms-shortcodes).

## Description

Easily map [WordPress style shortcodes](https://support.wordpress.com/shortcodes/) to custom templates or PHP class handlers. Use the provided Twig filter, `shortcodes` (or `sc`), to parse shortcodes in field content.

This plugin is a simple wrapper for the [`thunderer/shortcode`](https://github.com/thunderer/Shortcode) PHP package.

## Installation

```bash
composer require samhernandez/craft-shortcodes
```

## Configuration

Add a file to the `/config` directory named `shortcodes.php`. That file should return an array that maps shortcode tags to templates that you create to handle each shortcode.

Config file: `/config/shortcodes.php`

```php
return [
    'map' => [
        'video' => '_shortcodes/video.twig',
        // ...
    ]
];
```

Now, any `[video]` shortcodes will now load your custom handler template.

## Twig Filter

Add the twig filter, `shortcodes`, to any field that might contain shortcodes.

```twig
<div>{{ entry.legacyContent | shortcodes }}</div>
```

There is a short alias, `sc`, if you prefer it.

```twig
<div>{{ entry.legacyContent | sc }}</div>
```

## Template Handlers

This section is probably all you need to skim unless you’re looking to handle shortcodes with PHP rather than Twig. If so, jump to the next section.

As mentioned, this plugin is a simple wrapper for the [thunderer/Shortcode](https://github.com/thunderer/Shortcode) PHP package. With that in mind, a Twig variable, `shortcode` is available in your mapped shortcode templates.

(If you’re curious, it’s an instance of [ParsedShortcode](https://github.com/thunderer/Shortcode/blob/master/src/Shortcode/ParsedShortcode.php), but the most useful methods are on the [AbstractShortcode](https://github.com/thunderer/Shortcode/blob/master/src/Shortcode/AbstractShortcode.php) class.)

The matched element for the current route (e.g. `entry`, `category`, etc.) is also available in the shortcode template just in case it’s helpful.

### Useful `shortcode` Methods

#### `getContent()`

Gets the textual content between the opening and closing shortcode tags.

If the shortcode signature looks like this:

```
[myshortcode]
    Some text.
[/myshortcode]
```

then:

```
shortcode.getContent() | trim == 'Some text.'
```

Without the `trim` filter it would include outer line breaks and whitespace.

#### `getName()`

If the shortcode signature looks like this:

```
[myshortcode param="value"]
```

then:

```
shortcode.getName() == 'myshortcode'
```

#### `getParameter()`

If the shortcode signature looks like this:

```
[img src="/image.jpg"]
```

then:

```
shortcode.getParameter('src') == '/image.jpg'
```

A default value can be provided as the second argument:

```
shortcode.getParameter('width', 300) == 300
```

The `width` parameter is not on the shortcode tag, so it defaults to `300`.

#### `getParameterAt()`

Retrieve an unnamed parameter by its zero-index position.

If the shortcode signature looks like this:

```
[youtube https://www.youtube.com/watch?v=GFze-Oj2UdA]
```

then:

```
shortcode.getParameterAt(0) == 'https://www.youtube.com/watch?v=GFze-Oj2UdA'
```

#### `getParameters()`

Retrieves all of the parameters as key/value pairs. Unnamed parameters will be keys rather than values.

If the shortcode signature looks like this:

```
[embed https://www.youtube.com/watch?v=GFze-Oj2UdA width=300]
```

then:

```
shortcode.getParameters(0) == {
    "https://www.youtube.com/watch?v=GFze-Oj2UdA": null,
    "width": 300
}
```

#### `hasContent()`

Returns `true` if there is textual content between the opening and closing tags.

#### `hasParameter('key')`

Returns `true` if the shortcode tag has given parameter key.

#### `hasParameters()`

Returns `true` if the shortcode has any parameters at all.

### Template Example

We’ll create a simple, contrived `video` shortcode handler; just enough to steer you in the right direction.

Assuming your config file (`/config/shortcodes.php`) looks like this:

```php
return [
    'map' => [
        'video' => '_shortcodes/video.twig',
        // ...
    ]
];
```

Create a template: `templates/_shortcodes/video.twig`

```twig
{#
  Shortcode signature: [video mp4="video.mp4" webm="video.webm"]
#}

{% set mp4 = shortcode.getParameter('mp4') %}
{% set webm = shortcode.getParameter('webm') %}

<video controls>
  {% if mp4 %}<source src="{{ mp4 }}" type="video/mp4">{% endif %}
  {% if webm %}<source src="{{ webm }}" type="video/webm">{% endif %}
</video>
```

This Twig example is demonstrated as PHP in the next section.

## PHP Class Handlers

To handle shortcodes with PHP, create a [callable class](http://php.net/manual/en/language.oop5.magic.php#object.invoke) and add it as a handler in the configuration file.

### Callable Class Example

We’ll create a simple, contrived `video` shortcode handler; just enough to steer you in the right direction. We’ll enable the example module that ships with Craft and drop a shortcode handler in there.

Enable the module in `/config/app.php` by uncommenting the commented lines that ship with Craft.

```php
return [
    'modules' => [
        'my-module' => \modules\Module::class,
    ],
    'bootstrap' => ['my-module'],
];
```

Add a `VideoHandler` class in the module folder, `modules/videohandler.php`

```php
<?php

namespace modules;

use samhernandez\shortcodes\handlers\ShortcodeHandlerInterface;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class VideoHandler implements ShortcodeHandlerInterface
{
    /**
     * This callable class will be used as a callback to
     * handle the `video` shortcode tag.
     *
     * @param ShortcodeInterface $shortcode
     * @return string
     * @see http://php.net/manual/en/language.oop5.magic.php#object.invoke
     */
    public function __invoke(ShortcodeInterface $shortcode)
    {
        $mp4 = $shortcode.getParameter('mp4');
        $webm = $shortcode.getParameter('webm');

        $html = '<video>';

        if ($mp4) {
            $html .= '<source src="'.$mp4.'" type="video/mp4">';
        }

        if ($webm) {
            $html .= '<source src="'.$webm.'" type="video/webm">';
        }

        $html .= '</video>';

        // Be sure to return html as raw Twig
        return TemplateHelper::getRaw($html);
    }
}
```

Now add the fully qualified class name to the config file, `config/shortcodes.php`.

```php
<?php

use modules\VideoHandler;

return [
    'map' => [
        'video' => VideoHandler::class
    ]
];
```

That's it!

## Advanced Config

There are two more config options: `parser` and `syntax`.

```php
<?php
use 'samhernandez/craft-shorcodes/Shortcodes'

return [
    'map' => [
        // ...
    ],
    'parser' => Shortcodes::PARSER_REGEX,
    'syntax' => ['@', '$', '!', '&', '~']
];
```

`parser` options:

* `Shortcodes::PARSER_REGULAR`
* `Shortcodes::PARSER_REGEX`
* `Shortcodes::PARSER_WORDPRESS` (default)

Read the `thunderer/Shortcode` [Parsing](https://github.com/thunderer/Shortcode#parsing) docs for more information about the "regular" and "regex" parsers.

If you need to use different symbols for shortcode singatures, use `syntax` as an array with the following:

1. Opening tag
2. Closing tag
3. Closing tag marker
4. Parameter value separator
5. Parameter value delimiter

Alternative syntaxes are ignored by the WordPress parser. You'll need to use the "regular" or "regex" parser.

Read the `thunderer/Shortcode` [Syntax](https://github.com/thunderer/Shortcode#syntax) docs for more information.

## Contributing

Pull requests are welcome.

I'd like to see some of the native [WordPress shortcodes](https://support.wordpress.com/shortcodes/) in Twig so they can be used with this plugin straight out of the bubble wrap.

A nicer icon would be welcome too.
