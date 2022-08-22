<?php
namespace verbb\shortcodes\models;

use verbb\shortcodes\Shortcodes;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public $map = [];
    public $parser = Shortcodes::PARSER_WORDPRESS;
    public $syntax = null;
}
