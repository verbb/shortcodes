<?php

namespace samhernandez\shortcodes\models;

use craft\base\Model;
use samhernandez\shortcodes\Shortcodes;

class Settings extends Model {
    public $map = [];
    public $parser = Shortcodes::PARSER_WORDPRESS;
    public $syntax = null;
}
