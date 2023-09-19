# Configuration
Create a `shortcodes.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'map' => [
            'video' => '_shortcodes/video.twig',
            // ...
        ],
    ],
];
```

Now, any `[video]` shortcodes will now load your custom handler template.
