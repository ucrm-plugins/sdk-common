<?php
declare(strict_types=1);

/***********************************************************************************************************************
 * router.php
 * ---------------------------------------------------------------------------------------------------------------------
 * A simple script to "re-write" production URLs when using the PHP Web Server for development.
 *
 * @example     .../_plugins/<plugin-name>/... => .../...
 *
 * @author      Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright   2022 Spaeth Technologies Inc.
 */

// Get and define the Plugin's name and base "production" URL.
$pluginName = json_decode(file_get_contents(__DIR__."/manifest.json"), true)["information"]["name"];
$pluginBase = "/_plugins/$pluginName/";

// IF the current request contains the above base URL...
if(isset($_SERVER) && isset($_SERVER["REQUEST_URI"]) && strpos($_SERVER["REQUEST_URI"], $pluginBase) !== false)
{
    // ...THEN "rewrite" the URL by stripping the base URL from the request.
    $rewrite = preg_replace("#(/crm)?$pluginBase#", "/", $_SERVER["REQUEST_URI"]);
    
    // And then redirect the request to the local server's equivalent URL.
    // NOTE: This will include any query parameters as well!
    header("Location: " . $rewrite);
    exit();
}

// OTHERWISE, simply let the built-in web server handle the request!
return false;