<?php
declare(strict_types=1);

/***********************************************************************************************************************
 * bootstrap.php
 * ---------------------------------------------------------------------------------------------------------------------
 * A common configuration and initialization file for all included Plugin scripts.
 *
 * @author      Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright   2022 Spaeth Technologies Inc.
 */

#region Request Manipulation

/* *********************************************************************************************************************
 * NOTES:
 * - This fixes the issues with the new vue-router mangling the request with the query string.
 * - We also drop ALL query xdebug_params that may be incoming for the SPA, as they are irrelevant at this point!
 * - Only web requests will be handled here, as this section will be skipped when inclusion occurs by a CLI script.
 *
 * EXAMPLES:
 *   .../public.php?                => .../public.php
 *   .../public.php?/               => .../public.php
 *   .../public.php?/index.html     => .../public.php
 *   .../public.php?/index.html&... => .../public.php
 */
if (isset($_SERVER) && isset($_SERVER["REQUEST_URI"]))
{
    // Get the Plugin's name and base "production" URL.
    $pluginName = json_decode(file_get_contents(__DIR__."/../manifest.json"), true)["information"]["name"];
    $pluginBase = "/_plugins/${pluginName}/";
    
    // Strip the Plugin's base from the URI, so we have the actual request URI.
    // NOTES:
    // - We also remove the "/crm" prefix, if it happens to be present!
    // - We only remove the base from the URI for examination, not from the actual request!
    $uri = preg_replace("#(/crm)?${pluginBase}#", "/", $_SERVER["REQUEST_URI"]);
    
    // IF the request is any partial variation of the front-controller's root route...
    if ($uri === "/public.php?" ||
        $uri === "/public.php?/" ||
        $uri === "/public.php?/index.html" ||
        strpos($uri, "/public.php?/index.html") !== false)
    {
        // ...THEN we need to "clean" the URI, and unset any query parameters...
        $uri = $_SERVER["REQUEST_URI"] = "/public.php";
        unset($_SERVER["QUERY_STRING"]);
        
        // ...AND redirect to the root route!
        header("Location: public.php");
        exit();
    }
    
    // OTHERWISE, we can let the front-controller handle the request directly...
}

#endregion

#region Autoloader & Aliases

require_once __DIR__."/vendor/autoload.php";

use App\Middleware\WebhookMiddleware;
use MVQN\HTTP\Slim\DefaultApp;
use MVQN\HTTP\Slim\Middleware\Routing\QueryStringRouter;
use MVQN\HTTP\Slim\Middleware\Views\TwigView;
use MVQN\HTTP\Twig\Extensions\QueryStringRoutingExtension;
use MVQN\Localization\Translator;
use MVQN\Localization\Exceptions\TranslatorException;
use MVQN\REST\RestClient;

use UCRM\Common\Config;
use UCRM\Common\Log;
use UCRM\Common\Plugin;
use UCRM\HTTP\Slim\Middleware\Authentication\Authenticators\PluginAuthenticator;
use UCRM\Sessions\SessionUser;
use UCRM\REST\Endpoints\Version;

use Slim\Http\Request;
use Slim\Http\Response;

use App\Settings;

#endregion

#region Initialization

// Initialize the Plugin SDK using this directory as the plugin root and passing along any desired options.
/** @noinspection PhpUnhandledExceptionInspection */
Plugin::initialize(__DIR__."/example/", [
    "modules" => [
        //Plugin::MODULE_HTTP, // Forces necessary configuration for the HTTP module.
        //Plugin::MODULE_SMTP, // Forces necessary configuration for the SMTP module.
        //Plugin::MODULE_REST, // Forces necessary configuration for the REST module.
    ]
]);

// Regenerate the Settings class, in case there are changes in the "manifest.json" file since the last script execution.
/** @noinspection PhpUnhandledExceptionInspection */
Plugin::createSettings("App", "Settings", __DIR__);

//echo \UCRM\Common\Mailer::getType()."\n";
//echo \UCRM\Common\Mailer::getGmailPassword()."\n";

/*
$mailer = \UCRM\Common\Mailer::getMailer();
$message = (new Swift_Message("Test Message"))
    ->setFrom([ "unms@mvqn.net" => "UNMS System" ])
    ->setTo([ "rspaeth@spaethtech.com" => "Ryan Spaeth" ])
    ->setBody("<h2>This is a test!</h2>", "text/html");
//$mailer->send($message);
*/

//echo "<pre>";
//var_dump($_ENV);
//echo "</pre>";


#endregion

#region REST Client

// Create the REST URL from an ENV variable (including .env[.*] files), the Plugin Settings or fallback to localhost.
$restUrl =
    rtrim(
        getenv("HOST_URL") ?:                                                           // .env.local (or ENV variable)
            Settings::UCRM_LOCAL_URL ?:                                                     // ucrm.json
                (isset($_SERVER['HTTPS']) ? "https://localhost/" : "http://localhost/"),        // By initial request
        "/") . "/api/v1.0";
// NOTE: The "/crm" prefix is not necessary, as the UNMS system currently proxies the request to where it is needed!

// Configure the REST Client...
/** @noinspection PhpUnhandledExceptionInspection */
RestClient::setBaseUrl($restUrl);
RestClient::setHeaders([
    // All returned values are currently in the "application/json" format.
    "Content-Type: application/json",
    // Set the App Key from an ENV variable (including any .env[.*] files) or from the Plugin Settings.
    "X-Auth-App-Key: " . (getenv("REST_KEY") ?: Settings::PLUGIN_APP_KEY)
]);

// Attempt to test the connection to the UCRM's REST API...
try
{
    // Get the Version from the REST API and log the information to the Plugin's logs.
    if(Plugin::mode() === Plugin::MODE_DEVELOPMENT)
        Log::info("Using REST URL:\n    '".$restUrl."'");
    
    /** @var Version $version */
    $version = Version::get();
    
    if(Plugin::mode() === Plugin::MODE_DEVELOPMENT)
        Log::info("REST API Test : '".$version."'");
}
catch(Exception $e)
{
    // We should have resolved all existing conditions that previously prevented successful connections!
    //if(Plugin::mode() === Plugin::MODE_DEVELOPMENT)
    Log::error($e->getMessage());
}

#endregion

#region Localization

// TODO: Move this into Plugin::initialize() ???
// Attempt to set the dictionary directory and "default" locale...
try
{
    Translator::setDictionaryDirectory(__DIR__ . "/translations/");
    /** @noinspection PhpUnhandledExceptionInspection */
    Translator::setCurrentLocale(str_replace("_", "-", Config::getLanguage()) ?: "en-US", true);
}
catch (TranslatorException $e)
{
    // TODO: Determine if we should simply fallback to "en-US" in the case of failure.
    Log::http("No dictionary could be found!");
}

#endregion

#region Application (Server)

/** @noinspection PhpUnhandledExceptionInspection */
$app = new DefaultApp([
    
    /* NOTE: All of the common dependencies for our needs are added by DefaultApp.
     * ...
     */
    
    "settings" => [
        // NOTE: Here we enable Slim's extra error details when in development mode.
        //"displayErrorDetails" => Plugin::mode() === Plugin::MODE_DEVELOPMENT,
        "displayErrorDetails" => true,
    ],
    
    // NOTE: We add the Twig instance here, as it contains values that will NOT be common to all applications!
    "twig" =>  new TwigView(
    
    // NOTES:
    // - This can be either a single path to the Templates or an array of multiple paths.
    // - These paths can be different than the ones specified using the built-in TemplateRoute.
        __DIR__."/views/",
        
        // NOTE: Pass any desired options to be used during the initialization of the Twig Environment.
        [
            "debug" => Plugin::mode() === Plugin::MODE_DEVELOPMENT
        ],
        
        // NOTE: Include any desired global values, which will be added to the "app.<name>" variable in Twig templates.
        [
            // "debug" is also automatically added from the Twig Environment above, but can be overridden here.
            "baseUrl" => rtrim(Settings::PLUGIN_PUBLIC_URL, "/public.php"),
            "baseScript" => "public.php",
        ]
    ),
    
    /* NOTE: Add additional (or override) dependencies to the Container here...
     * ...
     */
]);

// NOTE: We can add additional global values at any time, but they will be overwritten by duplicates passed above!
QueryStringRoutingExtension::addGlobal("pluginName", Settings::PLUGIN_NAME);

/***********************************************************************************************************************
 * Logging
 * ---------------------------------------------------------------------------------------------------------------------
 * Add context information here for use by the Logging system for ALL requests...
 */

$app->add(
    function (Request $request, Response $response, $next)
    {
        // IF the Plugin is in development mode...
        if(Plugin::mode() === Plugin::MODE_DEVELOPMENT)
        {
            // ...THEN log ALL HTTP requests.
            Log::debug(
                $request->getAttribute("vRoute"),
                Log::HTTP,
                [
                    "route" => $request->getAttribute("vRoute"),
                    "query" => $request->getAttribute("vQuery"),
                    "user" => $request->getAttribute("sessionUser"),
                ]
            );
        }
        
        // AND continue to the next middleware.
        return $next($request, $response);
    }
);

/***********************************************************************************************************************
 * Authentication
 * ---------------------------------------------------------------------------------------------------------------------
 * NOTES:
 * - We setup the default permissions to allow the "Admin Group" only.
 * - And then load any existing groups into the allowed permissions.
 *
 * TODO: Add the ability to allow "Users" in addition to "Groups".
 */

$allowedGroups = [ "Admin Group" ];

// IF a permissions file exists...
if(file_exists(__DIR__."/../data/permissions.json"))
{
    // ...THEN open it and parse the data.
    $json = json_decode(file_get_contents(__DIR__."/../data/permissions.json"), true);
    
    // IF there are no parsing errors, THEN add any permitted groups to the allowed permissions.
    if(json_last_error() === JSON_ERROR_NONE)
        $allowedGroups = $json["groups"];
}

// NOTES:
// - When adding the AuthenticationHandler here, the application-level authentication middleware can NOT be overridden
//   in the individual groups and routes.
// - Adding the AuthenticationHandler to the groups and routes is the recommended method.

//$app->add(new AuthenticationHandler($app->getContainer()));

// NOTES:
// - This is the application-level authentication middleware, so any route using the AuthenticationHandler will use this
//   Authenticator by default.
// - Individual routes or groups can include their own Authenticator(s) that will override this one.
$app->add(new PluginAuthenticator(
    function(?SessionUser $user) use ($allowedGroups): bool
    {
        //var_dump($user, $allowedGroups);
        //exit();
        
        // Apply your own logic here and return TRUE/FALSE to authenticate successfully/unsuccessfully.
        return ($user !== null && in_array($user->getUserGroup(), $allowedGroups));
    }
));

/***********************************************************************************************************************
 * Front-Controller
 * ---------------------------------------------------------------------------------------------------------------------
 * WARNING: The following QueryStringRouter middleware bypasses the current restrictions that UCRM places on Plugins
 * with multiple pages and should be used at the developer's discretion!
 *
 * The QueryStringRouter handles URLs as follows:
 * - The Plugin's 'public.php' script acts as a front-controller that parses the query-string for the requested URL.
 *
 * EXAMPLE URLs:
 * - /public.php                                            => Loads the default route, as configured above.
 * - /public.php?/                                          => Same as the previous.
 * - /public.php?/index.html                                => Same as the previous, fully qualified URL.
 * - /public.php?/index.html&param1=1&param2=two            => Same as the previous, parameters stripped!
 *
 * - /public.php?[route][&param=value...]                   => All other suffixes are handled by Slim Framework routes.
 *   > /public.php?/                                        => Will route match "/".
 *   > /public.php?/example                                 => Will route match "/example".
 *   > /public.php?/test&data=123                           => Will route match "/test" with query string "data=123".
 *
 * - /public.php#[route]                                    => Our VueJS 'index.html' page and using vue-router syntax.
 *   > /public.php#/editor                                  => Will vue-route match "/editor"
 *   > /public.php#/logs                                    => Will vue-route match "/logs"
 *   > /public.php#/settings                                => Will vue-route match "/settings"
 *
 * Visit https://github.com/mvqn/ucrm-plugin-sdk for additional information on my extended UCRM SDK.
 *
 * NOTES:
 * - Any routes containing "/public/" are rewritten with "/", so that the public assets can be accessed using either the
 *   long or short form URL, where "/public/css/main.css" is the same as "/css/main.css".
 * - The QueryStringRouter should handle "building" the route before anything else, with the possible exception of the
 *   WebhookMiddleware only.  This is due to the fact that Webhooks are handled directly by this "public.php" script!
 */

$app->add(new QueryStringRouter("/", [ "#/public/#" => "/" ]));

/***********************************************************************************************************************
 * Webhook Events
 * ---------------------------------------------------------------------------------------------------------------------
 * Handles Webhook Events, as needed!
 */

$app->add(new WebhookMiddleware());

#endregion

#region Additional Bootstrapping

// IF a custom bootstrap file exists, THEN include it!
if($customPath = realpath(__DIR__ . "/bootstrap.inc.php"))
    /** @noinspection PhpIncludeInspection */
    include $customPath;

// IF the UCRM version is defined...
if(defined(Settings::class."::UCRM_VERSION"))
{
    /** @noinspection PhpUndefinedClassConstantInspection */
    $version = Settings::UCRM_VERSION;
    
    // ...AND IF a version bootstrap file exists, THEN include it!
    if($versionPath = realpath(__DIR__. "/bootstrap/$version.php"))
        /** @noinspection PhpIncludeInspection */
        include $versionPath;
    
    // ...AND IF a custom version bootstrap file exists, THEN include it!
    if($customVersionPath = realpath(__DIR__. "/bootstrap/$version.inc.php"))
        /** @noinspection PhpIncludeInspection */
        include $customVersionPath;
}

#endregion