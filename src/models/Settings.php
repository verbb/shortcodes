<?php
namespace verbb\shortcodes\models;

use verbb\shortcodes\Shortcodes;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public array $map = [];
    public string $parser = Shortcodes::PARSER_WORDPRESS;
    public mixed $syntax = null;
}
