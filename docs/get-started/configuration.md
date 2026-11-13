# Configuration

You can customise Shortcodes’ settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `shortcodes.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will map a `notice` shortcode to a template:

```php
<?php

return [
    'map' => [
        'notice' => '_shortcodes/notice.twig',
    ],
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `map`

**Type:** `array` · **Default:** `[]`

Maps shortcode names to handlers. The opening example uses a Twig template path relative to your project's templates directory. Add further names to the same map for other reusable content.
:::

::: reference
### `maxInputLength`

**Type:** `int` · **Default:** `1048576`

Sets the maximum number of bytes that Shortcodes will parse in one value. Oversized rich-text values are returned without expanding shortcodes, while oversized plain-text values remain HTML-encoded. This protects page renders from excessive parser work. Set this to `0` only when another trusted layer enforces an equivalent limit.
:::
