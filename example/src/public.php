<?php /** @noinspection PhpUnusedParameterInspection */
declare(strict_types=1);

require_once __DIR__."/server/vendor/autoload.php";

die(__PLUGIN_NAME__);

require_once __DIR__."/server/bootstrap.php";





use MVQN\HTTP\Slim\Middleware\Authentication\AuthenticationHandler;
use MVQN\HTTP\Slim\Middleware\Authentication\Authenticators\FixedAuthenticator;
use Slim\Http\Request;
use Slim\Http\Response;

use MVQN\HTTP\Slim\Routes\AssetRoute;
use MVQN\HTTP\Slim\Routes\TemplateRoute;
use MVQN\HTTP\Slim\Routes\ScriptRoute;

use App\Controllers;

/**
 * Use an immediately invoked function here, to avoid global namespace pollution...
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 *
 */
(function() use ($app) //, $container)
{
    $container = $app->getContainer();
    
    // Define a route using a closure...
    $app->get("/example/{name}",
        function (Request $request, Response $response, array $args)
        use ($container)
        {
            return $response->withJson([
                "name" => $args["name"],
                "description" => "This is an example JSON route!"
            ]);
        }
    )->add(new AuthenticationHandler($container))->add(new FixedAuthenticator(false));
    
    
    
    
    // NOTE: You can include any valid route syntax supported by the Slim Framework.  All routes and controllers placed
    // here will override any built-in controllers added below.  This is the perfect location to place API (server-side)
    // routes for consumption by the client-side code provided by VueJS in this Plugin.
    
    // TODO: Add additional custom routes or controllers here!
    // ...
    
    
    
    // =================================================================================================================
    // COMMON ROUTES
    // NOTE: These are common controllers used in the plugin template.
    // =================================================================================================================
    
    // Append a route handler for accessing the Plugin's log files.
    //new Controllers\API\LogsController($app);
    (new Controllers\ApiController($app))->add(new AuthenticationHandler($container));
    // TODO: Authentication!!!!
    
    // =================================================================================================================
    // BUILT-IN ROUTES
    // NOTE: These controllers should be added last, so the above controllers can override routes as needed.
    // =================================================================================================================
    
    // Append a route handler for static assets.
    (new AssetRoute(
        $app,
        __DIR__."/public/"
    //null // By providing NULL here, we effectively "undo" the application-level authentication middleware.
    ));//->add(new AuthenticationHandler($container))->add(new FixedAuthenticator(false));
    
    // Append a route handler for Twig templates.
    (new TemplateRoute(
        $app,
        __DIR__."/server/views/"
    ))->add(new AuthenticationHandler($container));//->add(new FixedAuthenticator(false));
    
    // Append a route handler for PHP scripts.
    (new ScriptRoute(
        $app,
        __DIR__."/server/src/"
    ))->add(new AuthenticationHandler($container));
    
    
    
    // Append a route handler for the default/root route...
    $app->get("/",
        function (Request $request, Response $response, array $args)
        use ($container)
        {
            // Directly output the HTML page, in our case the client entry point!
            return $response->write(
                file_get_contents(__DIR__ . "/index.html")
            );
        }
    )->add(new AuthenticationHandler($container)); // This simply uses the application-level authentication middleware.
    
    $app->post("/",
        
        function ( /** @noinspection PhpUnusedParameterInspection */ Request $request, Response $response, array $args)
        use ($container)
        {
            //echo "TESTING!";
            
            return $response->withJson([ "public" => "success" ]);
            
            //return $response->withRedirect("public.php?/webhook");
            
            
            
        }
    
    );
    
    // =================================================================================================================
    // APPLICATION EXECUTION
    // =================================================================================================================
    
    // Run the Slim Framework Application!
    $app->run();
    
})();