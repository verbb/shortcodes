# Configuration

You can customise Shortcodes’ settings using a PHP configuration file. The file is optional, but you need a mapping when a shortcode should render one of your Twig templates.

Create `shortcodes.php` in your Craft project's `/config` directory. This example maps a `notice` shortcode to a template:

```php
<?php

return [
    'map' => [
        'notice' => '_shortcodes/notice.twig',
    ],
];
```

Create `templates/_shortcodes/notice.twig` with the content you want to display:

```twig
<p class="notice">Our office is closed on public holidays.</p>
```

Add `[notice]` to a text field, then apply the `shortcodes` filter when rendering that field. See [Usage](docs:feature-tour/usage) for passing context and handling rich-text paragraphs.

## Configuration Options

::: reference
### `map`

**Type:** `array` · **Default:** `[]`

Maps shortcode names to handlers. The opening example uses a Twig template path relative to your project's templates directory. Add further names to the same map for other reusable content.
:::
