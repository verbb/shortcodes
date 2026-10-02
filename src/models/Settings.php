<?php
namespace verbb\shortcodes\models;

use verbb\shortcodes\Shortcodes;

use craft\base\Model;

class Settings extends Model
{
    // Constants
    // =========================================================================

    public const DEFAULT_MAX_INPUT_LENGTH = 1_048_576;


    // Properties
    // =========================================================================

    public array $map = [];
    public int $maxInputLength = self::DEFAULT_MAX_INPUT_LENGTH;
    public string $parser = Shortcodes::PARSER_WORDPRESS;
    public mixed $syntax = null;
}
