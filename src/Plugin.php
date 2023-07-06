<?php ///** @noinspection PhpUnused, SqlNoDataSourceInspection */
//declare(strict_types=1);
//
//namespace SpaethTech\UCRM\SDK;
//
//use DateTime;
//use Defuse\Crypto\Crypto;
//use Defuse\Crypto\Exception\BadFormatException;
//use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
//use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
//use Defuse\Crypto\Key;
//use Deployment;
//use Dotenv\Dotenv;
//use Exception;
//use SpaethTech\UCRM\SDK\Support\Arrays;
//use Nette\PhpGenerator\PhpNamespace;
//use PDO;
//use PDOException;
//use Symfony\Component\Yaml\Yaml;
//use Spaethtech\UCRM\SDK\Exceptions\PluginNotInitializedException;
//
///**
// * @copyright 2022 Spaeth Technologies Inc.
// * @author    Ryan Spaeth (rspaeth@spaethtech.com)
// *
// * Class Plugin
// *
// * @package UCRM\Common
// * @final
// */
//final class Plugin
//{
//
//    #region CONSTANTS
//
//    /*******************************************************************************************************************
//     * NOTES:
//     *   - By default the below will generate the settings in a file named "Settings.php" that lives in a folder named
//     *     "App" in the "plugin" folder of including plugin's root folder.
//     *     Example: `__PLUGIN_DIR__/server/App/Settings.php`
//     *
//     *   - The PSR-4 class name will be \App\Settings
//     ******************************************************************************************************************/
//
//    /** @var string The default namespace for the Settings singleton. */
//    private const _DEFAULT_SETTINGS_NAMESPACE = "App";
//
//    /** @var string The default class name for the Settings singleton. */
//    private const _DEFAULT_SETTINGS_CLASSNAME = "Settings";
//
//    private const _DEFAULT_SOURCE_PATH = "server/src/";
//
//    public const MODE_PRODUCTION    = "production";
//    public const MODE_DEVELOPMENT   = "development";
//    public const MODE_TESTING       = "testing";
//
//    #region INITIALIZATION
//
//    /** @var bool Determines whether the Plugin has already been initialized. */
//    private static bool $_initialized = false;
//
//    /**
//     * Initializes the Plugin singleton.
//     *
//     * This method MUST be called before any other Plugin methods.
//     *
//     * @param string|null $root The "root" path of this Plugin, if NULL, this method will attempt to guess it!
//     *
//     * @throws Exceptions\RequiredDirectoryNotFoundException
//     * @throws Exceptions\RequiredFileNotFoundException
//     */
//    public static function initialize(string $root = __PLUGIN_DIR__)
//    {
//        //file_put_contents(PLUGIN_DIR."/data/plugin.log", "TESTING!", FILE_APPEND | LOCK_EX);
//
//
//        #region REQUIRED: /
//
//        // Get the absolute "root" path, in cases where a relative path is provided.
//        $root = realpath($root);
//
//        // IF the root path is invalid or does not exist...
//        if(!$root || !file_exists($root) || !is_dir($root))
//        {
//            // THEN throw an Exception, as we cannot do anything else without this path!
//            throw new Exceptions\RequiredDirectoryNotFoundException(
//                "The provided root path does not exist!\n".
//                "- Provided: '$root'\n");
//        }
//
//        // IF the root path is not a folder...
//        if(!is_dir($root))
//        {
//            // THEN throw an Exception, as we cannot do anything else without this path!
//            throw new Exceptions\RequiredDirectoryNotFoundException(
//                "The provided root path is a file and should be a folder!\n".
//                "- Provided: '$root'\n");
//        }
//
//        #endregion
//
//        #region OPTIONAL: .env[.local]
//
//        // IF an .env file exists in the project, THEN load it!
//        if(file_exists($root."/.env"))
//            (new Dotenv($root, ".env"))->load();
//
//        if(file_exists($root."/.env.local"))
//            (new Dotenv($root, ".env.local"))->load();
//
//        #endregion
//
//        #region REQUIRED: /manifest.json
//
//        // Get the absolute "manifest.json" path, relative to the "root" path.
//        $manifest = realpath($root."/manifest.json");
//
//        // IF the manifest.json path is invalid or does not exist...
//        if(!$manifest || !file_exists($manifest) || !is_file($manifest))
//        {
//            // NOTE: This is a required Plugin file, so it should ALWAYS exist!
//            // THEN throw an Exception, as we cannot do anything else without this file!
//            throw new Exceptions\RequiredFileNotFoundException("\n".
//                "The provided root path does not contain a 'manifest.json' file!\n".
//                "- Provided: '$root'\n");
//        }
//
//        #endregion
//
//        #region REQUIRED: /ucrm.json
//
//        // Get the absolute "ucrm.json" path, relative to the "root" path.
//        $ucrm = realpath($root."/ucrm.json");
//
//        // IF the ucrm.json path is invalid or does not exist...
//        if(!$ucrm || !file_exists($ucrm) || !is_file($ucrm))
//        {
//            // NOTE: This is a required Plugin file, so it should ALWAYS exist!
//            // THEN throw an Exception, as we cannot do anything else without this file!
//            throw new Exceptions\RequiredFileNotFoundException(
//                "The provided root path does not contain a 'ucrm.json' file!\n".
//                "- Provided: '$root'\n");
//        }
//
//        #endregion
//
//        #region REQUIRED: /data/
//
//        // Get the absolute "data" path, relative to the "root" path.
//        $data = realpath($root."/data/");
//
//        // IF the data path is invalid or does not exist...
//        if(!$data || !file_exists($data))
//        {
//            // NOTE: By performing this check after the Plugin's required files, we can now simply create this folder!
//            mkdir($root."/data/", 0775, TRUE);
//
//            /*
//            // THEN throw an Exception, as we cannot do anything else without this path!
//            throw new Exceptions\RequiredDirectoryNotFoundException(
//                "The provided root path '$root' does not contain a 'data' directory!\n".
//                "- Provided: '$root/data/'\n");
//            */
//        }
//
//        // TODO: Determine the need to handle a valid data path when a non-directory file exists?
//
//        #endregion
//
//        #region REQUIRED: /data/config.json
//
//        // Get the absolute "config.json" path, relative to the "root" path.
//        $config = realpath($root."/data/config.json");
//
//        // IF the config.json path is invalid or does not exist...
//        if(!$config || !file_exists($config) || !is_file($config))
//        {
//            $json = json_encode(
//                [
//                    "development" => false,
//                    // NOTE: Add any other default config to be added here...
//                ],
//                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
//            );
//
//            // NOTE: By performing this check after the Plugin's required files, we can now simply create this file!
//            file_put_contents($root."/data/config.json", $json);
//        }
//
//        #endregion
//
//        #region OPTIONAL: /data/plugin.log
//
//        /*
//
//        // Get the absolute "plugin.log" path, relative to the "root" path.
//        $log = realpath($root."/data/plugin.log");
//
//        // IF the plugin.log path is invalid or does not exist...
//        if(!$log || !file_exists($log) || !is_file($log))
//        {
//            $trace = debug_backtrace()[0];
//
//            // NOTE: By performing this check after the Plugin's required files, we can now simply create this file!
//            $entry = new LogEntry([
//                "timestamp" => (new DateTimeImmutable())->format(LogEntry::TIMESTAMP_FORMAT_DATETIME),
//                "channel" => "UCRM",
//                "level" => Logger::INFO,
//                "levelName" => "INFO",
//                "message" => "This plugin.log file has been automatically generated by Plugin::initialize().",
//                "context" => [],
//                "extra" => [
//                    "file" => $trace["file"],
//                    "line" => $trace["line"],
//                    "class" => $trace["class"],
//                    "function" => $trace["function"],
//                    //"args" => $trace["args"],
//                ],
//            ]);
//
//            file_put_contents($root."/data/plugin.log", $entry);
//        }
//
//        */
//
//        #endregion
//
//        #region OPTIONAL: /src/
//
//        // Get the absolute "data" path, relative to the "root" path.
//        $src = realpath($root."/" . self::_DEFAULT_SOURCE_PATH);
//
//        // IF the data path is invalid or does not exist...
//        if(!$src || !file_exists($src))
//        {
//            // NOTE: By performing this check after the Plugin's required files, we can now simply create this folder!
//            mkdir($root."/" . self::_DEFAULT_SOURCE_PATH, 0775, TRUE);
//        }
//
//        #endregion
//
//        // All required/optional checks have passed, so this must be a valid Plugin root path!
//        self::$_rootPath = $root;
//
//        // TODO: Add any further Plugin initialization code here!
//        // ...
//
//        self::$_initialized = true;
//    }
//
//    public static function stderrToLog(): void
//    {
//        fclose(STDERR);
//        $STDERR = fopen(PLUGIN_DIR."/data/plugin.log", "w");
//    }
//
//    /**
//     * @return bool Returns TRUE if the Plugin has been initialized, otherwise FALSE.
//     */
//    public static function isInitialized(): bool
//    {
//        return self::$_initialized;
//    }
//
//    #endregion
//
//    #region PATHS
//
//    /**
//     * @var string The root path of this Plugin, as configured by Plugin::initialize();
//     */
//    private static $_rootPath = "";
//
//    /**
//     * Gets the "root" path.
//     *
//     * @return string Returns the absolute ROOT path of this Plugin.
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function getRootPath(): string
//    {
//        // IF the plugin is not initialized, THEN throw an Exception!
//        if(self::$_rootPath === "")
//            throw new Exceptions\PluginNotInitializedException(
//                "The Plugin must be initialized using 'Plugin::initialize()' before calling any other methods!\n");
//
//        // Finally, return the ROOT path!
//        return self::$_rootPath;
//    }
//
//    /**
//     * Gets the "source" path.
//     *
//     * @return string Returns the absolute SOURCE path of this Plugin.
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function getSourcePath(): string
//    {
//        // Get the ROOT path, which will also throw the PluginNotInitializedException if necessary.
//        $root = self::getRootPath();
//
//        // NOTE: This is now handled in Plugin::initialize().
//        // IF the directory does not exist, THEN create it...
//        if(!file_exists("$root/".self::_DEFAULT_SOURCE_PATH))
//            mkdir("$root/".self::_DEFAULT_SOURCE_PATH);
//
//        // Finally, return the DATA path!
//        return realpath("$root/".self::_DEFAULT_SOURCE_PATH);
//    }
//
//    /**
//     * Gets the "data" path.
//     *
//     * @return string Returns the absolute DATA path of this Plugin.
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function getDataPath(): string
//    {
//        // Get the ROOT path, which will also throw the PluginNotInitializedException if necessary.
//        $root = self::getRootPath();
//
//        // NOTE: This is now handled in Plugin::initialize().
//        // IF the directory does not exist, THEN create it...
//        if(!file_exists("$root/data/"))
//            mkdir("$root/data/");
//
//        // Finally, return the DATA path!
//        return realpath("$root/data/");
//    }
//
//    /**
//     * Gets the "logs" path.
//     *
//     * @return string Returns the absolute LOGS path of this Plugin.
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function getLogsPath(): string
//    {
//        // Get the ROOT path, which will also throw the PluginNotInitializedException if necessary.
//        $data = self::getDataPath();
//
//        // NOTE: This is now handled in Plugin::initialize().
//        // IF the directory does not exist, THEN create it...
//        if(!file_exists("$data/logs/"))
//            mkdir("$data/logs/");
//
//        // Finally, return the DATA path!
//        return realpath("$data/logs/");
//    }
//
//    #endregion
//
//    #region METADATA
//
//    /**
//     * @param string $path
//     *
//     * @return array|string|mixed
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function manifest( string $path = "" )
//    {
//        $manifest = json_decode( file_get_contents( self::getRootPath() . "/manifest.json" ), true );
//
//        if( $path === null || $path === "" )
//            return $manifest;
//
//        return Arrays::path($manifest, $path, ".");
//    }
//
//    /**
//     * @param string $path
//     *
//     * @return array|string|mixed
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function ucrm(string $path = "")
//    {
//        $ucrm = json_decode(file_get_contents(self::getRootPath() . "/ucrm.json"), true);
//
//        if($path === null || $path === "")
//            return $ucrm;
//
//        return Arrays::path($ucrm, $path, ".");
//    }
//
//    #endregion
//
//    #region PERMISSIONS
//
//
//
//    #endregion
//
//    #region STATES
//
//    /**
//     * Checks to determine if the Plugin is currently pending execution, via manual/scheduled execution.
//     *
//     * @return bool Returns TRUE if this Plugin is pending execution, otherwise FALSE.
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function isExecuting(): bool
//    {
//        // Get the ROOT path, which will also throw the PluginNotInitializedException if necessary.
//        $root = self::getRootPath();
//
//        // Return TRUE if the UCRM specified file exists!
//        return file_exists("$root/.ucrm-plugin-execution-requested");
//    }
//
//    // TODO: Upon feedback from UBNT, determine if it is possible to use something like Plugin::requestExecution()?
//
//    /**
//     * Checks to determine if the Plugin is currently executing, via manual/scheduled execution.
//     *
//     * @return bool Returns TRUE if this Plugin is currently executing, otherwise FALSE.
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function isRunning(): bool
//    {
//        // Get the ROOT path, which will also throw the PluginNotInitializedException if necessary.
//        $root = self::getRootPath();
//
//        // Return TRUE if the UCRM specified file exists!
//        return file_exists("$root/.ucrm-plugin-running");
//    }
//
//    #endregion
//
//    #region SETTINGS
//
//    /**
//     * @var string
//     */
//    private static $_settingsFile = "";
//
//    /**
//     * Generates a class with auto-implemented methods and then saves it to a PSR-4 compatible file.
//     *
//     * @param string $namespace An optional namespace to use for the settings file, defaults to "MVQN\UCRM\Plugins".
//     * @param string $class An optional class name to use for the settings file, defaults to "Settings".
//     * @param string|null $path
//     *
//     * @throws Exceptions\ManifestElementException
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function createSettings(string $namespace = self::_DEFAULT_SETTINGS_NAMESPACE,
//                                          string $class = self::_DEFAULT_SETTINGS_CLASSNAME, string $path = null): void
//    {
//        // Get the root path for this Plugin, throws an Exception if not already initialized.
//        $root = self::getRootPath();
//
//        // TODO: Test the need for DIRECTORY_SEPARATOR here...
//        // Generate the source path based on namespace using PSR-4 standards for composer.
//        $path = ($path === null ? self::getSourcePath() : $path)."/".str_replace("\\", "/", $namespace);
//
//        // IF the path does not already exist, THEN create it, recursively (as needed)!
//        if(!file_exists($path))
//            mkdir($path, 0777, true);
//
//        // Clean-Up the absolute path.
//        $path = realpath($path);
//
//        // Create the namespace.
//        $_namespace = new PhpNamespace($namespace);
//
//        // Add the necessary 'use' statements.
//        $_namespace->addUse(DateTime::class);
//        $_namespace->addUse(SettingsBase::class);
//
//        // Create and add the new Settings class.
//        $_class = $_namespace->addClass($class);
//
//        // Set the necessary parts of the class.
//        $_class
//            ->setFinal()
//            ->setExtends(SettingsBase::class)
//            ->addComment("@copyright 2022 Spaeth Technologies Inc.")
//            ->addComment("@author    Ryan Spaeth (rspaeth@spaethtech.com)\n");
//
//        #region Project
//
//        $projectPath = dirname($root) === "/data/ucrm/data/plugins" ? $root : dirname($root);
//
//        //$_class->addConstant("PROJECT_NAME", basename(realpath(Plugin::getRootPath()."/../")))
//        $_class->addConstant("PROJECT_NAME", basename($projectPath))
//            ->setVisibility("public")
//            ->addComment("@const string The name of this Project, based on the root folder name.");
//
//        //$_class->addConstant("PROJECT_ROOT_PATH", realpath(Plugin::getRootPath()."/../"))
//        $_class->addConstant("PROJECT_ROOT_PATH", $projectPath)
//            ->setVisibility("public")
//            ->addComment("@const string The absolute path to this Project's root folder.");
//
//        #endregion
//
//        #region Plugin
//
//        $_class->addConstant("PLUGIN_NAME", self::manifest("information/name"))
//            ->setVisibility("public")
//            ->addComment("@const string The name of this Plugin, based on the manifest information.");
//
//        $_class->addConstant("PLUGIN_ROOT_PATH", self::getRootPath())
//            ->setVisibility("public")
//            ->addComment("@const string The absolute path to the root folder of this project.");
//
//        $_class->addConstant("PLUGIN_DATA_PATH", self::getDataPath())
//            ->setVisibility("public")
//            ->addComment("@const string The absolute path to the data folder of this project.");
//
//        $_class->addConstant("PLUGIN_LOGS_PATH", self::getLogsPath())
//            ->setVisibility("public")
//            ->addComment("@const string The absolute path to the logs folder of this project.");
//
//        $_class->addConstant("PLUGIN_SOURCE_PATH", self::getSourcePath())
//            ->setVisibility("public")
//            ->addComment("@const string The absolute path to the source folder of this project.");
//
//        #endregion
//
//        #region ucrm.json
//
//        //$ucrm = json_decode(file_get_contents($root."/ucrm.json"), true);
//        $ucrm = self::ucrm();
//
//        // IF the UCRM's public URL is not set...
//        if($ucrm["ucrmPublicUrl"] === null)
//        {
//            // OTHERWISE, set the UCRM's public URL to that of the public facing IP address?
//
//
//            // Get the current external IP address by lookup to "checkip.dyndns.com".
//            $externalContent = file_get_contents('http://checkip.dyndns.com/');
//            preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)]?/', $externalContent, $m);
//            $externalIp = $m[1];
//
//            // Assume HTTP, as without a FQDN set in UCRM, there cannot be a valid certificate!
//            $ucrm["ucrmPublicUrl"] = "http://$externalIp/";
//
//            //Log::info("This UCRM's public URL has been set dynamically to: {$ucrm['ucrmPublicUrl']}");
//
//            // TODO: Determine if we should set this in the "ucrm.json" file to cache future lookups?
//        }
//
//        $_class
//            ->addConstant("UCRM_PUBLIC_URL", rtrim($ucrm["ucrmPublicUrl"], "/"))
//            ->setVisibility("public")
//            ->addComment("@const string|null The public URL of this UCRM server, null if not configured.");
//
//        $_class
//            ->addConstant("UCRM_LOCAL_URL", $ucrm["ucrmLocalUrl"] !== null ? rtrim($ucrm["ucrmLocalUrl"], "/") : null)
//            ->setVisibility("public")
//            ->addComment("@const string|null The local URL of this UCRM server, null if not configured.");
//
//        // IF this plugin is installed on UNMS 1.0.0-beta.1 or above, THEN this key should exist...
//        if(array_key_exists("unmsLocalUrl", $ucrm))
//        {
//            $unmsLocalUrl = $ucrm["unmsLocalUrl"];
//
//            $_class
//                ->addConstant("UNMS_LOCAL_URL", $unmsLocalUrl !== null ? rtrim($unmsLocalUrl, "/") : null)
//                ->setVisibility("public")
//                ->addComment("@const string|null The local URL of this UNMS server, null if not configured.");
//        }
//
//        // IF this plugin's public URL is not set AND there is a "public.php" file present...
//        if($ucrm["pluginPublicUrl"] === null && file_exists($root."/public.php"))
//        {
//            // OTHERWISE, set the Plugin's public URL dynamically?
//            $ucrm["pluginPublicUrl"] =
//                "{$ucrm['ucrmPublicUrl']}_plugins/" . __PLUGIN_NAME__ . "/public.php";
//
//            //Log::info("This Plugin's public URL has been set dynamically to: {$ucrm['pluginPublicUrl']}");
//        }
//
//        $_class->addConstant("PLUGIN_PUBLIC_URL", $ucrm["pluginPublicUrl"])
//            ->setVisibility("public")
//            ->addComment("@const string The public URL of this UCRM server, null if not configured.");
//
//        // NOTE: This should NEVER really happen!
//        // IF there is no App Key setup for this plugin...
//        if($ucrm["pluginAppKey"] === null)
//        {
//            die("'pluginAppKey' not found in ucrm.json!");
//        }
//
//        $_class->addConstant("PLUGIN_APP_KEY", $ucrm["pluginAppKey"])
//            ->setVisibility("public")
//            ->addComment("@const string An automatically generated UCRM API 'App Key' with read/write access.");
//
//        $_class->addConstant("PLUGIN_ID", $ucrm["pluginId"])
//            ->setVisibility("public")
//            ->addComment("@const string An automatically generated UCRM Plugin ID.");
//
//        #endregion
//
//        #region version.yml
//
//        if(file_exists("/usr/src/ucrm/app/config/version.yml"))
//        {
//            // THEN, parse the file and add the following constants to the Settings!
//            $version = Yaml::parseFile("/usr/src/ucrm/app/config/version.yml")["parameters"];
//
//            $_class->addConstant("UCRM_VERSION", $version["version"])
//                ->setVisibility("public")
//                ->addComment("@const string The UCRM server's current version.");
//
//            $_class->addConstant("UCRM_VERSION_STABILITY", $version["version_stability"])
//                ->setVisibility("public")
//                ->addComment("@const string The UCRM server's current version stability.");
//        }
//
//        #endregion
//
//        #region parameters.yml
//
//        if(file_exists("/usr/src/ucrm/app/config/parameters.yml"))
//        {
//            $parameters = Yaml::parseFile("/usr/src/ucrm/app/config/parameters.yml")["parameters"];
//
//            $_class->addConstant("UCRM_DB_DRIVER", $parameters["database_driver"])
//                ->setVisibility("public")
//                ->addComment("@const string The UCRM Database Driver.");
//
//            // NOTE: This is "localhost" in UNMS 1.0.0-beta.1 and above, but used to be "postgresql".
//            $_class->addConstant("UCRM_DB_HOST", $parameters["database_host"])
//                ->setVisibility("public")
//                ->addComment("@const string The UCRM Database Host.");
//
//            $_class->addConstant("UCRM_DB_NAME", $parameters["database_name"])
//                ->setVisibility("public")
//                ->addComment("@const string The UCRM Database Name.");
//
//            $_class->addConstant("UCRM_DB_PASSWORD", $parameters["database_password"])
//                ->setVisibility("public")
//                ->addComment("@const string The UCRM Database Password.");
//
//            $_class->addConstant("UCRM_DB_PORT", $parameters["database_port"])
//                ->setVisibility("public")
//                ->addComment("@const string The UCRM Database Port.");
//
//            $_class->addConstant("UCRM_DB_USER", $parameters["database_user"])
//                ->setVisibility("public")
//                ->addComment("@const string The UCRM Database User.");
//        }
//
//        #endregion
//
//        #region Configuration
//
//        // Loop through each key/value pair in the file...
//        foreach(self::manifest("configuration") as $setting)
//        {
//            // Create a new Setting for each element, parsing the given values.
//            $_setting = new Setting($setting);
//
//            // Append the '|null' suffix to the type, if the value is NOT required.
//            $type = $_setting->type.(!$_setting->required ? "|null" : "");
//
//            // Add the property to the current Settings class.
//            $_property = $_class->addProperty($_setting->key);
//
//            // Set the necessary parts of the property.
//            $_property
//                ->setVisibility("protected")
//                ->setStatic()
//                ->addComment($_setting->label)
//                ->addComment("@var $type " . $_setting->description);
//
//            // Generate the name of the AutoObject's getter method for this property.
//            $getter = "get".ucfirst($_setting->key);
//
//            // And then append it to the class comments for Annotation lookup and IDE auto-completion.
//            $_class->addComment("@method static $type $getter()");
//        }
//
//        #endregion
//
//        // Generate the code for the Settings file.
//        /** @noinspection PhpToStringMayProduceExceptionInspection */
//        $code = "<?php " . /** @lang PHP */ <<<EOF
//            /** @noinspection PhpUnused, PhpUnusedAliasInspection, SpellCheckingInspection, HttpUrlsUsage */
//            declare(strict_types=1);
//
//            $_namespace;
//            EOF;
//
//        // Hack to add an extra line break between const declarations, as Nette\PhpGenerator does NOT!
//        $code = str_replace(";\n\t/** @const", ";\n\n\t/** @const", $code);
//        // Hack to remove trailing semi-colon.
//        $code = preg_replace("#\n^;$#m", "\n", $code);
//
//        // Generate and set the Settings file absolute path.
//        self::$_settingsFile = $path."/".$class.".php";
//
//        // Save the code to the file location.
//        file_put_contents(self::$_settingsFile, $code);
//
//    }
//
//    /**
//     * @param string $name The name of the constant to append to this Settings class.
//     * @param mixed $value The value of the constant to append to this Settings class.
//     * @param string $comment An optional comment for this constant.
//     * @return bool Returns TRUE if the constant was successfully appended, otherwise FALSE.
//     * @throws Exception
//     */
//    public static function appendSettingsConstant(string $name, $value, string $comment = ""): bool
//    {
//        // IF the Settings file not been assigned or the file does not exist...
//        if(self::$_settingsFile === "" || !file_exists(self::$_settingsFile))
//            // Attempt to create the Settings now!
//            self::createSettings();
//
//        // Now load the Settings file contents.
//        $code = file_get_contents(self::$_settingsFile);
//
//        // Find all the occurrences of the constants using RegEx, getting the file positions as well.
//        //$constRegex = "/(\/\*\* @const (?:[\w\|\[\]]+).*\*\/)[\r|\n|\r\n]+(?:.*;[\r|\n|\r\n]+)([\r|\n|\r\n]+)/m";
//        $constRegex = "/(\/\*\* @const [\w|\[\]]+.*\*\/)[\r|\n]+.*;[\r|\n]+([\r|\n]+)/m";
//        preg_match_all($constRegex, $code, $matches, PREG_OFFSET_CAPTURE);
//
//        // IF there are no matches found OR the matches array does not contain the offsets part...
//        if($matches === null || count($matches) !== 3)
//            // THEN return failure!
//            return false;
//
//        // Get the position of the very last occurrence of the matches.
//        $position = $matches[2][count($matches[2]) - 1][1];
//
//        // Get the type of the "mixed" value as to set it correctly in the constant field...
//        switch(gettype($value))
//        {
//            case "boolean":
//                $typeString = "bool";
//                $valueString = $value ? "true" : "false";
//                break;
//            case "integer":
//                $typeString = "int";
//                $valueString = "$value";
//                break;
//            case "double":
//                $typeString = "float";
//                $valueString = "$value";
//                break;
//            case "string":
//                $typeString = "string";
//                $valueString = "'$value'";
//                break;
//            case "array":
//            case "object":
//            case "resource":
//            case "NULL":
//                // NOT SUPPORTED!
//                return false;
//
//            case "unknown type":
//            default:
//                // Cannot determine key components, so return!
//                return false;
//        }
//
//        // Generate the new constant code.
//        $const = "\r\n".
//            "\t/** @const $typeString".($comment ? " ".$comment : "")." */\r\n".
//            "\tpublic const $name = $valueString;\r\n";
//
//        // Append the new constant code after the last existing constant in the Settings file.
//        $code = substr_replace($code, $const, $position, 0);
//
//        // Save the contents over the existing file.
//        file_put_contents(self::$_settingsFile, $code);
//
//        // Finally, return success!
//        return true;
//    }
//
//    #endregion
//
//    #region ENCRYPTION / DECRYPTION
//
//    /**
//     * Gets the cryptographic key from the UCRM file system.
//     *
//     * @return Key
//     * @throws Exceptions\CryptoKeyNotFoundException
//     * @throws Exceptions\PluginNotInitializedException
//     * @throws BadFormatException
//     * @throws EnvironmentIsBrokenException
//     */
//    public static function getCryptoKey(): Key
//    {
//        // Set the path to the cryptographic key.
//        $path = self::getRootPath() . "/../../encryption/crypto.key";
//
//        // IF the file exists at the correct location, THEN return key, OTHERWISE return null!
//        if(file_exists($path))
//            return Key::loadFromAsciiSafeString(file_get_contents($path));
//
//        // Handle DEV environment!
//        if(getenv("CRYPTO_KEY") !== false)
//            return Key::loadFromAsciiSafeString(getenv("CRYPTO_KEY"));
//
//        throw new Exceptions\CryptoKeyNotFoundException("File not found at: '$path'!\n");
//    }
//
//    // -----------------------------------------------------------------------------------------------------------------
//    /**
//     * Decrypts a string using the provided cryptographic key.
//     *
//     * @param string $string The string to decrypt.
//     * @param Key|null $key The key to use for decryption, or automatic detection if not provided.
//     * @return string Returns the decrypted string.
//     * @throws Exceptions\CryptoKeyNotFoundException
//     * @throws Exceptions\PluginNotInitializedException
//     * @throws BadFormatException
//     * @throws EnvironmentIsBrokenException
//     * @throws WrongKeyOrModifiedCiphertextException
//     */
//    public static function decrypt(string $string, Key $key = null): string
//    {
//        // Set the key specified; OR if not provided, get the key from the UCRM file system.
//        $key = $key ?? self::getCryptoKey();
//
//        // Decrypt and return the string!
//        return Crypto::decrypt($string, $key);
//    }
//
//    // -----------------------------------------------------------------------------------------------------------------
//    /**
//     * Encrypts a string using the provided cryptographic key.
//     *
//     * @param string $string The string to encrypt.
//     * @param Key|null $key The key to use for decryption, or automatic detection if not provided.
//     * @return string Returns the encrypted string.
//     * @throws Exceptions\CryptoKeyNotFoundException
//     * @throws Exceptions\PluginNotInitializedException
//     * @throws BadFormatException
//     * @throws EnvironmentIsBrokenException
//     */
//    public static function encrypt(string $string, Key $key = null): ?string
//    {
//        // Set the key specified; OR if not provided, get the key from the UCRM file system.
//        $key = $key ?? self::getCryptoKey();
//
//        // Encrypt and return the string!
//        return Crypto::encrypt($string, $key);
//    }
//
//    #endregion
//
//    #region ENVIRONMENT
//
//
//
//    public static function isRunningOnUCRM()
//    {
//        if (file_exists("/usr/src/ucrm"))
//            return true;
//    }
//
//    /**
//     * @param string $default
//     * @return string
//     * @throws PluginNotInitializedException
//     */
//    public static function mode(string $default = Plugin::MODE_PRODUCTION): string
//    {
//        if(!Plugin::isInitialized())
//            return $default;
//
//        $configPath = realpath(self::getDataPath() . "/config.json");
//
//        if($configPath)
//        {
//            $config = json_decode(file_get_contents($configPath), true);
//
//            if (json_last_error() === JSON_ERROR_NONE && array_key_exists("development", $config))
//                return $config["development"] ? self::MODE_DEVELOPMENT : self::MODE_PRODUCTION;
//        }
//
//        return $default;
//    }
//
//    #endregion
//
//    #region DATABASE (plugin.db)
//
//    /**
//     * @var PDO|null The Plugin's internal database handle.
//     */
//    private static $_pdo = null;
//
//    /**
//     * Gets the Plugin's internal database handle.
//     *
//     * @return PDO|null Returns the database handle, or NULL if not connected!
//     */
//    public static function dbPDO() : ?PDO
//    {
//        return self::$_pdo;
//    }
//
//    /**
//     * Gets the Plugin's internal database path.
//     *
//     * @return string Returns the database path, even if it does not exist.
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function dbPath(): string
//    {
//        return self::getDataPath() . DIRECTORY_SEPARATOR . "plugin.db";
//    }
//
//    /**
//     * Connects to the Plugin's internal database.
//     *
//     * @return PDO|null Returns the database handle if the connection was successful, otherwise NULL!
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function dbConnect(): ?PDO
//    {
//        // IF a database handle does not already exist...
//        if(!self::$_pdo)
//        {
//            try
//            {
//                self::$_pdo = new PDO(
//                    "sqlite:" . self::dbPath(),
//                    null,
//                    null,
//                    [
//                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
//                    ]
//                );
//            }
//            catch(PDOException $e)
//            {
//                return null;
//                //http_response_code(400);
//                //die("The Plugin's Database could not be opened!\n$e");
//            }
//        }
//
//        // OTHERWISE, return the existing handle.
//        return self::$_pdo;
//    }
//
//    /**
//     * Queries the Plugin's internal database.
//     *
//     * @param string $statement The query to execute, must be valid SQL syntax.
//     *
//     * @return array Returns an array of fetched results.
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function dbQuery(string $statement, array $params = [], ?string $class = null): array
//    {
//        try
//        {
//            $sql = self::dbConnect()->prepare( $statement );
//            $sql->execute( $params );
//
//            $results = ($class !== null)
//                ? $sql->fetchAll( PDO::FETCH_CLASS, $class )
//                : $sql->fetchAll();
//
//            //$results = self::dbConnect()->query($statement)->fetchAll();
//            //return $results ?: [];
//            /*
//            $pdo = self::dbConnect();
//            $results = ($class !== null)
//                ? $pdo->query($statement)->fetchAll( PDO::FETCH_CLASS, $class )
//                : $pdo->query($statement)->fetchAll();
//            */
//
//            return $results ?: [];
//        }
//        catch(PDOException $e)
//        {
//            http_response_code(400);
//            die("The Plugin's Database could not be accessed!\n$e");
//        }
//    }
//
//    /**
//     * Closes the Plugin's internal database connection.
//     */
//    public static function dbClose(): void
//    {
//        if(self::$_pdo !== null)
//            self::$_pdo = NULL;
//    }
//
//    /**
//     * Deletes the Plugin's internal database, closing the connection as needed.
//     *
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function dbDelete(): void
//    {
//        self::dbClose();
//        unlink(self::dbPath());
//    }
//
//    /**
//     *
//     * @param string $name
//     *
//     * @return bool
//     *
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function dbTableExists(string $name): bool
//    {
//        return count( self::dbQuery( /** @lang SQLite */ <<<EOF
//            -- noinspection SqlResolve
//            SELECT *
//            FROM `sqlite_master`
//            WHERE `type` = 'table' AND `name` = :name;
//            EOF
//            , [ ":name" => $name ] ) ) > 0;
//    }
//
//    /**
//     *
//     * @return Permissions\Permission[]
//     *
//     * @throws Exceptions\PluginNotInitializedException
//     */
//    public static function permissions(): array
//    {
//        if( !self::dbTableExists("permissions") )
//        {
//            self::dbQuery( /** @lang SQLite */ <<<EOF
//                CREATE TABLE `permissions` (
//                    `id`         INTEGER PRIMARY KEY AUTOINCREMENT,
//                    `group_id`   TEXT,
//                    `group_name` TEXT,
//                    `user_id`    INTEGER,
//                    `user_name`  TEXT,
//                    `type`       TEXT NOT NULL,
//                    `key`        TEXT NOT NULL,
//                    `value`      TEXT NOT NULL,
//                    `allowed`    INTEGER
//                );
//                EOF
//            );
//
//            self::dbQuery( /** @lang SQLite */ <<<EOF
//                -- noinspection SqlResolve
//                INSERT INTO `permissions`
//                VALUES (
//                    null, null, 'Admin Group', null, null, :type, :key, :value, true
//                )
//                EOF,
//                [
//                    ":type" => Permissions\PermissionType::ROUTE,
//                    ":key" => "api",
//                    ":value" => "/api/*",
//                ]
//            );
//
//        }
//
//        return self::dbQuery( /** @lang SQLite */ <<<EOF
//            -- noinspection SqlResolve
//            SELECT *
//            FROM `permissions`
//            EOF,
//            [],
//            Permissions\Permission::class
//        );
//
//    }
//
//
//
//    #endregion
//
//
//    /**
//     *
//     * @param string|null $path
//     *
//     * @return array|string|false
//     */
//    public static function parameters(?string $path = null)
//    {
//        if( __DEPLOYMENT__ !== Deployment::REMOTE )
//        {
//            if( defined( "ENV_LOADED" ) )
//            {
//                return [
//
//                    "database_driver" => getenv("DATABASE_DRIVER"),
//                    "database_host" => getenv("DATABASE_HOST"),
//                    "database_port" => getenv("DATABASE_PORT"),
//                    "database_name" => getenv("DATABASE_NAME"),
//                    "database_user" => getenv("DATABASE_USER"),
//                    "database_password" => getenv("DATABASE_PASSWORD"),
//                    "database_schema_ucrm" => getenv("DATABASE_SCHEMA_UCRM"),
//                    "database_schema_unms" => getenv("DATABASE_SCHEMA_UNMS"),
//
//                    // NOTE: Add any other parameters as needed!
//                ];
//            }
//
//            return FALSE;
//        }
//
//        $yaml = file_exists( "/usr/src/ucrm/app/config/parameters.yml" )
//            ? Yaml::parseFile( "/usr/src/ucrm/app/config/parameters.yml" )["parameters"]
//            : [];
//
//        return $path ? Arrays::path( $yaml, $path, "." ) : $yaml;
//
//    }
//
//
//
//
//    #region Locators
//
//    /**
//     * Finds a Plugin's path given it's ID.
//     *
//     * @param int $id The ID of the Plugin to locate.
//     *
//     * @return false|string Returns FALSE if the ID was not found, or the absolute path to the plugin.
//     *
//     * @throws PluginNotInitializedException
//     */
//    public static function findPluginPathById(int $id)
//    {
//        $basePath = dirname(Plugin::getRootPath());
//
//        foreach(scandir($basePath) as $folder)
//        {
//            if($folder === "." || $folder === "..")
//                continue;
//
//            if (($currentPath = realpath("$basePath/$folder")) &&
//                ($ucrmPath = realpath("$currentPath/ucrm.json")))
//            {
//                $ucrm = json_decode(file_get_contents($ucrmPath), true);
//
//                if (json_last_error() !== JSON_ERROR_NONE ||
//                    !isset($ucrm["pluginId"]) ||
//                    $ucrm["pluginId"] !== $id)
//                    continue;
//
//                return $currentPath;
//            }
//        }
//
//        return false;
//    }
//
//    /**
//     * Finds a Plugin's path given it's Name.
//     *
//     * @param string $name The Name of the Plugin to locate.
//     *
//     * @return false|string Returns FALSE if the Name was not found, or the absolute path to the plugin.
//     *
//     * @throws PluginNotInitializedException
//     */
//    public static function findPluginPathByName(string $name)
//    {
//        $basePath = dirname(Plugin::getRootPath());
//
//        foreach(scandir($basePath) as $folder)
//        {
//            if($folder === "." || $folder === "..")
//                continue;
//
//            if (($currentPath = realpath("$basePath/$folder")) &&
//                ($manifestPath = realpath("$currentPath/manifest.json")))
//            {
//                $manifest = json_decode(file_get_contents($manifestPath), true);
//
//                if (json_last_error() !== JSON_ERROR_NONE ||
//                    !isset($manifest["information"]) ||
//                    !isset($manifest["information"]["name"]) ||
//                    $manifest["information"]["name"] !== $name)
//                    continue;
//
//                return $currentPath;
//            }
//        }
//
//        return false;
//    }
//
//    #endregion
//
//
//    #region Loaders
//
//    /**
//     * Loads a JSON file and the decodes the object into an associative array.
//     *
//     * @param string $path The path to the JSON file.
//     * @return array|null Returns FALSE if the file does not exist, or an associative array containing of decoded JSON.
//     */
//    public static function loadJson(string $path): ?array
//    {
//        if (($real = realpath($path)) && (is_file($real)))
//        {
//            $data = json_decode(file_get_contents($real), true);
//
//            if(json_last_error() === JSON_ERROR_NONE && $data)
//                return $data;
//        }
//
//        return null;
//    }
//
//    #endregion
//
//}
