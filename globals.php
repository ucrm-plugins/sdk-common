<?php /** @noinspection PhpUnused, SpellCheckingInspection */
declare(strict_types=1);

/**
 * @copyright   2022 Spaeth Technologies Inc.
 * @author      Ryan Spaeth (rspaeth@spaethtech.com)
 *
 * globals.php
 *
 * A collection of magic constants to ease plugin development.
 *
 * IMPORTANT: Many of my libraries depend on these constants, so be mindful of what you're doing when editing this file!
 *
 */

#region CONTAINER

if ( !defined( "__CONTAINER_ID__" ) )
    define( "__CONTAINER_ID__", PHP_OS !== "WINNT" ? exec( "cat /proc/1/cpuset | cut -c9-" ) : "" );

#endregion

#region DEPLOYMENT

if( !defined( "__DEPLOYMENT__" ) )
{
    class Deployment
    {
        public const REMOTE = "REMOTE";
        public const LOCAL = "LOCAL";
    }
    
    define( "__DEPLOYMENT__", ( strpos( __CONTAINER_ID__, exec( "echo \$HOSTNAME" ) ) === 0 ) ? "REMOTE" : "LOCAL" );
}

#endregion

if( !defined( "__UCRM_VERSION__" ) )
{
    $path = "/usr/src/ucrm/app/config/version.yml";
    define("__UCRM_VERSION__", file_exists($path) ? yaml_parse_file($path)["version"] : "" );
}


#region PROJECT

if (!defined("__PROJECT_DIR__"))
{
    define("__PROJECT_DIR__", realpath(__DIR__."/../../../../../"));
}

if (!defined("__PROJECT_NAME__"))
{
    $path = realpath( __PROJECT_DIR__."/composer.json" );
    
    $name = $path ? json_decode(file_get_contents($path), TRUE)["name"] : basename(__PROJECT_DIR__);
    
    $name = strpos($name, "/") !== false ? explode("/", $name)[1] : $name;
    
    define("__PROJECT_NAME__", $name);
}


#endregion

#region PLUGIN

if (!defined("__PLUGIN_DIR__"))
{
    $path = __PROJECT_DIR__ . (__DEPLOYMENT__ === DEPLOYMENT::REMOTE ? "" : "/src");
    define("__PLUGIN_DIR__", realpath($path));
}

if (!defined("__PLUGIN_NAME__"))
{
    define("__PLUGIN_NAME__", file_exists(__PLUGIN_DIR__."/manifest.json")
        ? json_decode(file_get_contents(__PLUGIN_DIR__."/manifest.json"), TRUE)["information"]["name"]
        : __PROJECT_NAME__
    );
}
#endregion

