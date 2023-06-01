<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

if (!defined("PROJECT_DIR"))
    define("PROJECT_DIR", realpath(__DIR__));

if (!function_exists("is_null_or_empty"))
{
    function is_null_or_empty(string|null $str): bool
    {
        return ($str === null || trim($str) === "");
    }
}
