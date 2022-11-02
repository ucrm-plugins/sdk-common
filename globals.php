<?php

declare(strict_types=1);

/**
 * @copyright 2022 Spaeth Technologies Inc.
 * @author Ryan Spaeth (rspaeth@spaethtech.com)
 *
 * globals.php
 *
 * A collection of magic constants to ease plugin development.
 *
 * IMPORTANT: Many of my libraries depend on these constants, so be mindful of what you're doing when editing this file!
 *
 * cspell:ignore cpuset
 */

if (!defined("CONTAINER_ID"))
    define("CONTAINER_ID", PHP_OS !== "WINNT" ? exec("cat /proc/1/cpuset | cut -c9-") : "");

if (!defined("DEPLOYMENT")) {
    class Deployment
    {
        public const REMOTE = "REMOTE";
        public const LOCAL = "LOCAL";
    }

    define("DEPLOYMENT", (strpos(CONTAINER_ID, exec("echo \$HOSTNAME")) === 0) ? "REMOTE" : "LOCAL");
}

if (!defined("UCRM_VERSION")) {
    $path = "/usr/src/ucrm/app/config/version.yml";

    define("UCRM_VERSION", file_exists($path) ? yaml_parse_file($path)["version"] : "");
}


if (!defined("PROJECT_DIR")) {
    define("PROJECT_DIR", realpath(__DIR__ . "/../../../../../"));
}

if (!defined("PROJECT_NAME")) {
    $path = realpath(PROJECT_DIR . "/composer.json");

    $name = $path ? json_decode(file_get_contents($path), TRUE)["name"] : basename(PROJECT_DIR);

    $name = strpos($name, "/") !== false ? explode("/", $name)[1] : $name;

    define("PROJECT_NAME", $name);
}


if (!defined("PLUGIN_DIR")) {
    $path = PROJECT_DIR . (DEPLOYMENT === DEPLOYMENT::REMOTE ? "" : "/src");
    define("PLUGIN_DIR", realpath($path));
}

if (!defined("PLUGIN_NAME")) {
    define(
        "PLUGIN_NAME",
        file_exists(PLUGIN_DIR . "/manifest.json")
        ? json_decode(file_get_contents(PLUGIN_DIR . "/manifest.json"), TRUE)["information"]["name"]
        : PROJECT_NAME
    );
}
