<?php
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK\REST;

use GuzzleHttp\Client;
use SpaethTech\UCRM\SDK\Support\Strings;
use Psr\Http\Message\ResponseInterface;

/**
 * RestClient
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022 Spaeth Technologies Inc.
 *
 */
final class RestClient
{
    private const JSON_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    
    private static $_guzzle;
    
    
    /** @var string The base URL with which all requests will be prefixed. */
    private static $_baseUrl = "";
    
    /** @var string[] Optional headers for which all requests will contain. */
    private static $_headers = [];
    
    
    private static $cachePath = null;
    
    private const CACHE_FOLDER = ".cache/mvqn/rest";
    
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Gets or sets the base URL to be used with all REST calls.
     *
     * @param string $url The base URL for which to prefix all REST calls.
     * @return string Returns the base URL that is currently set.
     * @throws \Exception Throws an exception if the base URL is not set and none has been provided.
     */
    public static function setBaseUrl(string $url = ""): string
    {
        // IF no URL has been specified...
        if($url === "" || $url === null)
        {
            // AND the current URL is not set...
            if (self::$_baseUrl === "")
                // ... Throw an exception!
                throw new \Exception("[MVQN\REST\ResClient] ".
                    "'baseUrl' must be set by RestClient::baseUrl() before calling any RestClient methods!");
        }
        else
        {
            // OTHERWISE set the URL to that which was specified here.
            self::$_baseUrl = $url;
            self::client(true);
        }
        
        // Finally, return the URL, which should NEVER be empty at this point!
        return self::$_baseUrl;
    }
    
    public static function getBaseUrl(): string
    {
        return self::$_baseUrl;
    }
    
    
    /**
     * @param array $headers
     * @return array
     */
    public static function setHeaders(array $headers = []): array
    {
        // IF no headers have been specified...
        if($headers === [] || $headers === null)
        {
            // AND the current Key is not set...
            //if (self::$_headers === [])
            // ... Throw an exception!
            //throw new RestClientException(
            //    "'ucrmKey' must be set by RestClient::ucrmKey() before calling any RestClient methods!");
        }
        else
        {
            // OTHERWISE set the Headers to those which were specified here.
            self::$_headers = $headers;
            self::client(true);
        }
        
        // Finally, return the Headers!
        return self::$_headers;
    }
    
    public static function getHeaders(): array
    {
        return self::$_headers ?? [];
    }
    
    public static function pushHeader(string $header): array
    {
        if(self::$_headers === null)
            self::$_headers = [];
        
        self::$_headers[] = $header;
        self::client(true);
        
        return self::$_headers;
    }
    
    public static function popHeader(): ?string
    {
        if(self::$_headers === null || self::$_headers === [])
            return null;
        
        $header = array_pop(self::$_headers);
        self::client(true);
        
        return $header;
    }
    
    // =================================================================================================================
    // STATIC METHODS: Caching
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * @param string|null $path If provided, sets the cache directory for annotations from this point forward.
     * @return string|null Returns the directory path, or NULL if caching is disabled.
     */
    public static function cacheDir(string $path = null): ?string
    {
        if($path !== null)
        {
            // Create the cache directory, if it does not exist...
            if (!file_exists(dirname($path)))
                mkdir(dirname($path), 0777, true);
            
            // Create a '.cache' directory inside the cache directory, if it does not exist...
            if (!file_exists(dirname($path."/".self::CACHE_FOLDER."/")))
                mkdir(dirname($path."/".self::CACHE_FOLDER."/"), 0777, true);
            
            // Set the cache path, statically, for future use.
            self::$cachePath = $path;
        }
        
        // Return the cache path, even if it is NULL!
        return self::$cachePath;
    }
    
    /**
     * @param string $directory The current directory to remove.
     * @internal
     */
    private static function removeDirectoryRecursive(string $directory)
    {
        if (is_dir($directory))
        {
            foreach (scandir($directory) as $object)
            {
                if ($object !== "." && $object !== "..")
                {
                    if (is_dir($directory."/".$object))
                        self::removeDirectoryRecursive($directory."/".$object);
                    else
                        unlink($directory."/".$object);
                }
            }
            
            rmdir($directory);
        }
    }
    
    /**
     * @param array|null $classes An optional array of class names for which to remove, ignoring the rest.
     */
    public static function cacheClear(array $classes = null): void
    {
        if(self::$cachePath !== null)
        {
            // IF no specific classes have been provided
            if($classes === null)
            {
                // Use a specific '.cache' directory as to avoid accidentally deleting undesired folders recursively.
                self::removeDirectoryRecursive(self::$cachePath."/".self::CACHE_FOLDER."/");
                return;
            }
            
            // Loop through each provided class and attempt to delete them individually...
            foreach($classes as $class)
            {
                $cacheFile = self::$cachePath."/".self::CACHE_FOLDER."/".$class;
                
                if(file_exists($cacheFile))
                    self::removeDirectoryRecursive($cacheFile);
            }
        }
    }
    
    
    public static function cachePath(string $class): string
    {
        //$namespace = explode("\\", $class);
        //$name = array_pop($namespace);
        //$namespace = implode("\\", $namespace);
        
        $cacheFile = self::$cachePath."/".self::CACHE_FOLDER."/$class.json";
        
        return $cacheFile;
    }
    
    
    
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Creates a cURL session with the necessary options, headers and endpoint to communicate with the UCRM Server.
     *
     * @param string $endpoint The endpoint at which to make the request.
     * @return resource Returns a cURL session.
     */
    private static function curl(string $endpoint)
    {
        // Get the base URL and App Key.
        $baseUrl = self::$_baseUrl;
        
        // IF a session currently exists, close it to ensure cURL does not hang!
        if (session_status() !== PHP_SESSION_NONE)
            session_write_close();
        
        // Create a cURL session.
        $curl = curl_init();
        
        // Set the options necessary for communicating with the UCRM Server.
        curl_setopt($curl, CURLOPT_URL, $baseUrl.$endpoint);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        
        // IF the Base URL is using HTTPS AND is requesting localhost...
        if(Strings::startsWith(self::$_baseUrl, "https://localhost"))
        {
            // THEN disable host/peer certificate checks, as localhost cannot resolve to a valid name for SSL!
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        else
        {
            // OTHERWISE, enable host/peer certificate checks, this is fine for all HTTP URLs (including localhost)!
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // DEFAULT
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // DEFAULT
            
            // Downloaded from: https://curl.haxx.se/docs/caextract.html
            curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . "/Certificates/cacert-2018-10-17.pem");
            curl_setopt($curl, CURLOPT_CAPATH, __DIR__ . "/Certificates/cacert-2018-10-17.pem");
        }
        
        // Set the necessary HTTP HEADERS.
        curl_setopt($curl, CURLOPT_HTTPHEADER, self::$_headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        
        return $curl;
    }
    
    public static function client(bool $refresh = false): Client
    {
        if(!self::$_guzzle || $refresh)
        {
            $headers = [];
            
            array_map(
                function ($value) use (&$headers) {
                    $parts = explode(":", $value);
                    
                    $key = "";
                    $val = "";
                    
                    if($parts && count($parts) > 0)
                        $key = trim($parts[0]);
                    
                    if($parts && count($parts) > 1)
                        $val = trim($parts[1]);
                    
                    $headers[$key] = $val;
                    
                    return;
                },
                self::$_headers
            );
            
            $verify = false;
            
            if (Strings::startsWith(self::$_baseUrl, "https")) {
                if (!Strings::contains(self::$_baseUrl, "localhost")) {
                    $verify = __DIR__ . "/Certificates/cacert-2018-10-17.pem";
                }
            }
            
            self::$_guzzle = new Client([
                "base_uri" => self::$_baseUrl . "/",
                "headers" => $headers,
                "verify" => $verify,
            ]);
        }
        
        return self::$_guzzle;
    }
    
    
    
    
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Sends a HTTP GET Request to the specified endpoint of the base URL.
     *
     * @param string $endpoint The endpoint at which to make the request.
     * @return array Returns an associative array of the JSON result.
     * @throws \Exception Throws an exception if there were errors during the REST request/response phase.
     */
    public static function get(string $endpoint): ?array
    {
        // Create the cURL session.
        $curl = self::curl($endpoint);
        
        // Execute the request and capture the response.
        $response = curl_exec($curl);
        
        // Check to see if there were any errors...
        if(!$response)
            throw new \Exception("[MVQN\REST\RestClient] The REST request failed with the following error(s): " .
                curl_error($curl));
        
        // Close the cURL session.
        curl_close($curl);
        
        /*
        $response = (string)self::client()
            ->get(ltrim($endpoint, "/"))
            ->getBody();
        */
        
        // Finally, return the resulting associative array!
        return json_decode($response, true);
    }
    
    /**
     * Sends multiple HTTP GET Request to the specified endpoints of the base URL.
     *
     * @param array $endpoints An array of endpoints to use for the multiple requests.
     * @return array Returns an associative array of the JSON results.
     * @throws \Exception Throws an exception if there were errors during the REST request/response phase.
     */
    public static function getMany(array $endpoints): array
    {
        // Create a cURL multi-session handler and an array to store each instance of the cURL sessions.
        $curl_handler = curl_multi_init();
        $curls = [];
        
        // Loop through each provided endpoint...
        for($i = 0; $i < count($endpoints); $i++)
        {
            // Create the cURL session.
            $curl = self::curl($endpoints[$i]);
            
            // Add the cURL session to the multi-session handler and store it in the array of sessions.
            curl_multi_add_handle($curl_handler, $curl);
            $curls[] = $curl;
        }
        
        $running = null;
        // Loop through and execute all of the cURL sessions in the multi-session handler...
        do curl_multi_exec($curl_handler, $running); while ($running);
        
        $responses = [];
        // Loop through each of the cURL sessions...
        for($i = 0; $i < count($curls); $i++)
        {
            // Get each session response and convert it to an associative array.
            $response = curl_multi_getcontent($curls[$i]);
            
            // Check to see if there were any errors...
            if(!$response)
                throw new \Exception("[MVQN\REST\ResClient] ".
                    "The REST request failed with the following error(s): ".curl_error($curls[$i]));
            
            //  Append the successful response to the array of responses.
            $responses[] = json_decode($response, true);
            
            // Then remove the cURL session from the multi-session handler.
            curl_multi_remove_handle($curl_handler, $curls[$i]);
        }
        
        // Close the cURL multi-session handler.
        curl_multi_close($curl_handler);
        
        // Finally, return the
        return $responses;
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Sends a HTTP POST Requests to the specified endpoint of the base URL.
     *
     * @param string $endpoint The endpoint at which to make the request.
     * @param array $data A JSON encoded string of data to provide to the endpoint.
     * @return array Returns an associative array of the JSON result.
     * @throws \Exception Throws an exception if there were errors during the REST request/response phase.
     */
    public static function post(string $endpoint, array $data, ResponseInterface &$response = null): array
    {
        /*
        // Create the cURL session.
        $curl = self::curl($endpoint);

        // Set any additional options.
        curl_setopt($curl, CURLOPT_POST, true);

        // Set the data to be provided to the endpoint.
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, self::JSON_OPTIONS));

        // Execute the request and capture the response.
        $response = curl_exec($curl);

        // Check to see if there were any errors...
        if(!$response)
            throw new \Exception("[MVQN\REST\ResClient] The REST request failed with the following error(s): ".curl_error($curl));

        // Close the cURL session.
        curl_close($curl);
        */
        
        $response = self::client()->post(ltrim($endpoint, "/"), [ "json" => $data ] );
        
        // Finally, return the resulting associative array!
        return json_decode((string)$response->getBody(), true) ?? [];
    }
    
    /**
     * Sends multiple HTTP POST Requests to the specified endpoints of the base URL.
     *
     * @param array $endpoints An array of endpoints to use for the multiple requests.
     * @param array[] $data An array of associative arrays of data to provide to the endpoints and converted to JSON.
     * @return array Returns an associative array of the JSON results.
     * @throws \Exception Throws an exception if there were errors during the REST request/response phase.
     */
    public static function postMany(array $endpoints, array $data): array
    {
        if(count($endpoints) !== count($data))
            throw new \Exception("[MVQN\REST\ResClient] ".
                "Each endpoint in a RestClient::postMany() call must have an accompanying data element.");
        
        // Create a cURL multi-session handler and an array to store each instance of the cURL sessions.
        $curl_handler = curl_multi_init();
        $curls = [];
        
        // Loop through each provided endpoint...
        for($i = 0; $i < count($endpoints); $i++)
        {
            // Create the cURL session.
            $curl = self::curl($endpoints[$i]);
            
            // Set any additional options.
            curl_setopt($curl, CURLOPT_POST, true);
            
            // Set the data to be provided to the endpoint.
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data[$i], self::JSON_OPTIONS));
            
            // Add the cURL session to the multi-session handler and store it in the array of sessions.
            curl_multi_add_handle($curl_handler, $curl);
            $curls[] = $curl;
        }
        
        $running = null;
        // Loop through and execute all of the cURL sessions in the multi-session handler...
        do curl_multi_exec($curl_handler, $running); while ($running);
        
        $responses = [];
        // Loop through each of the cURL sessions...
        for($i = 0; $i < count($curls); $i++)
        {
            // Get each session response and convert it to an associative array.
            $response = curl_multi_getcontent($curls[$i]);
            
            // Check to see if there were any errors...
            if(!$response)
                throw new \Exception("[MVQN\REST\ResClient] ".
                    "The REST request failed with the following error(s): ".curl_error($curls[$i]));
            
            //  Append the successful response to the array of responses.
            $responses[] = json_decode($response, true);
            
            // Then remove the cURL session from the multi-session handler.
            curl_multi_remove_handle($curl_handler, $curls[$i]);
        }
        
        // Close the cURL multi-session handler.
        curl_multi_close($curl_handler);
        
        // Finally, return the
        return $responses;
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Sends a HTTP PATCH Request to the specified endpoint of the base URL.
     *
     * @param string $endpoint The endpoint at which to make the request.
     * @param array $data A JSON encoded string of data to provide to the endpoint.
     * @return array Returns an associative array of the JSON result.
     * @throws \Exception Throws an exception if there were errors during the REST request/response phase.
     */
    public static function patch(string $endpoint, array $data): array
    {
        /*
        // Create the cURL session.
        $curl = self::curl($endpoint);

        // Set any additional options.
        //curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");

        // Set the data to be provided to the endpoint.
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, self::JSON_OPTIONS));

        // Execute the request and capture the response.
        $response = curl_exec($curl);

        // Check to see if there were any errors...
        if(!$response)
            throw new \Exception("[MVQN\REST\ResClient] The REST request failed with the following error(s): ".curl_error($curl));

        // Close the cURL session.
        curl_close($curl);
        */
        
        $response = (string)self::client()->patch(ltrim($endpoint, "/"), [ "json" => $data ] )->getBody();
        
        // Finally, return the resulting associative array!
        return json_decode($response, true);
    }
    
    
    public static function delete(string $endpoint): array
    {
        /*
        // Create the cURL session.
        $curl = self::curl($endpoint);

        // Set any additional options.
        //curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");

        // Set the data to be provided to the endpoint.
        //curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, self::JSON_OPTIONS));

        // Execute the request and capture the response.
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Check to see if there were any errors...
        //if($response)
        //    throw new \Exception("[MVQN\REST\ResClient] The REST request failed with the following error(s): ".curl_error($curl));

        if($httpCode !== 200)
            return json_decode($response, true);

        // Close the cURL session.
        curl_close($curl);
        */
        
        $response = (string)self::client()->delete(ltrim($endpoint, "/"))->getBody();
        
        // Finally, return the resulting associative array!
        //return json_decode($response, true);
        return [];
    }
    
    /**
     * Sends a HTTP GET Request to the specified endpoint of the base URL.
     *
     * @param string $endpoint The endpoint at which to make the request.
     * @return mixed
     * @throws \Exception Throws an exception if there were errors during the REST request/response phase.
     */
    public static function download(string $endpoint)
    {
        // Create the cURL session.
        $curl = self::curl($endpoint);
        
        // Execute the request and capture the response.
        $response = curl_exec($curl);
        
        // Check to see if there were any errors...
        if(!$response)
            throw new \Exception("[MVQN\REST\RestClient] The REST request failed with the following error(s): "
                .curl_error($curl));
        
        // Close the cURL session.
        curl_close($curl);
        
        // Finally, return the resulting associative array!
        return $response;
    }
    
}
