<?php
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK;

use App\Settings;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Dotenv\Dotenv;
use PDO;
use ReflectionException;
use SpaethTech\UCRM\SDK\Data\Database;
use SpaethTech\UCRM\SDK\Data\Tables\OptionTable;
use SpaethTech\UCRM\SDK\Dynamics\AutoObject;
use SpaethTech\UCRM\SDK\Exceptions\CryptoKeyNotFoundException;
use SpaethTech\UCRM\SDK\Exceptions\DatabaseConnectionException;
use SpaethTech\UCRM\SDK\Exceptions\ModelClassException;

/**
 * Class Config
 * @package MVQN\UCRM\Plugins
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 *
 *
 * @method static string|null getLanguage()
 * @method static string|null getSmtpTransport()
 * @method static string|null getSmtpUsername()
 * @method static string|null getSmtpPassword()
 * @method static string|null getSmtpHost()
 * @method static string|null getSmtpPort()
 * @method static string|null getSmtpEncryption()
 * @method static string|null getSmtpAuthentication()
 * @method static bool|null   getSmtpVerifySslCertificate()
 * @method static string|null getSmtpSenderEmail()
 * @method static string|null getGoogleApiKey()
 * @method static string|null getTimezone()
 * @method static string|null getServerIP()
 * @method static string|null getServerFQDN()
 * @method static int|null    getServerPort()
 */
final class Config extends AutoObject
{
    /** @var string */
    protected static $language;

    /** @var string */
    protected static $smtpTransport;

    /** @var string */
    protected static $smtpUsername;

    /** @var string */
    protected static $smtpPassword;

    /** @var string */
    protected static $smtpHost;

    /** @var string */
    protected static $smtpPort;

    /** @var string */
    protected static $smtpEncryption;

    /** @var string */
    protected static $smtpAuthentication;

    /** @var bool */
    protected static $smtpVerifySslCertificate;

    /** @var string */
    protected static $smtpSenderEmail;

    /** @var string */
    protected static $googleApiKey;

    /** @var string */
    protected static $timezone;

    /** @var string */
    protected static $serverIP;

    /** @var string */
    protected static $serverFQDN;

    /** @var int */
    protected static $serverPort;

    /** @var PDO */
    protected static PDO $pdo;

    /**
     * Executes prior to the very fist static __call() method and used to initialize the properties of this class.
     *
     * @return bool
     * @throws Exceptions\CryptoKeyNotFoundException
     * @throws Exceptions\PluginNotInitializedException
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws DatabaseConnectionException
     * @throws ModelClassException
     * @throws ReflectionException
     */
    public static function __beforeFirstStaticCall(): bool
    {
        // =============================================================================================================
        // ENVIRONMENT
        // =============================================================================================================

        // Get the path to an optional .env file for development.
        $envPath = realpath(Plugin::getRootPath()."/../");

        // IF an .env file exists, THEN initialize environment variables from the .env file!
        if (file_exists($envPath . "/.env") && (!defined("ENV_LOADED") || !ENV_LOADED))
        {
            (new Dotenv($envPath))->load();

            if (!defined("ENV_LOADED"))
                define("ENV_LOADED", true);
        }

        // =============================================================================================================
        // SETTINGS
        // =============================================================================================================

        // Initialize the Plugin libraries using the current directory as the root.
        //Plugin::initialize(__DIR__ . "/../../../");

        // Regenerate the Settings class, in case anything has changed in the manifest.json file.
        //Plugin::createSettings();

        // Create a database connection using environment variables, from either an .env file or the actual environment.
        // NOTE: These variables all exist on the production servers!


        // TODO: Best way to handle this when an .env is NOT provided in development?
        Database::connect(
            getenv("POSTGRES_HOST") ?: Settings::UCRM_DB_HOST,
            (int)getenv("POSTGRES_PORT") ?: Settings::UCRM_DB_PORT,
            getenv("POSTGRES_DB") ?: Settings::UCRM_DB_NAME,
            getenv("POSTGRES_USER") ?: Settings::UCRM_DB_USER,
            getenv("POSTGRES_PASSWORD") ?: Settings::UCRM_DB_PASSWORD
        );


        // Generate the Cryptographic Key used by the Crypto library from the has already created by the UCRM server.
        // NOTE: The '../../encryption/crypto.key' file will not exist in development environments and the crypto hash
        // will need to be included in an .env file for decryption to work in development!
        try
        {
            $cryptoKey = Plugin::getCryptoKey() ?? Key::loadFromAsciiSafeString(getenv("CRYPTO_KEY"));
        }
        catch(CryptoKeyNotFoundException $e)
        {
            // TODO: Determine the best way to handle this; currently, we just set $cryptoKey to null!
            $cryptoKey = null;
        }

        // Get a collection of all rows of the option table from the database!
        $options = OptionTable::select();

        /** @var OptionTable $option */

        // LANGUAGE/LOCALE
        $option = $options->where("code", "APP_LOCALE")->first();
        self::$language = $option->getValue();


        // SMTP TRANSPORT
        $option = $options->where("code", "MAILER_TRANSPORT")->first();
        self::$smtpTransport = $option ? $option->getValue() : null;

        // SMTP USERNAME
        $option = $options->where("code", "MAILER_USERNAME")->first();
        self::$smtpUsername = $option ? $option->getValue() : null;

        // SMTP PASSWORD
        $option = $options->where("code", "MAILER_PASSWORD")->first();
        if($option && $option->getValue() !== null)
            self::$smtpPassword = ($option->getValue() !== "" && $cryptoKey !== null) ? Plugin::decrypt($option->getValue(), $cryptoKey) : null;

        //if (self::$smtpPassword === null || self::$smtpPassword === "")
        //    Log::error("SMTP Password could not be determined by UCRM Settings!", \Exception::class);

        // SMTP HOST
        $option = $options->where("code", "MAILER_HOST")->first();
        self::$smtpHost = self::$smtpTransport === "gmail" ? "smtp.gmail.com" : ($option ? $option->getValue() : null);

        // SMTP PORT
        $option = $options->where("code", "MAILER_PORT")->first();
        self::$smtpPort = self::$smtpTransport === "gmail" ? "587" : ($option ? $option->getValue() : null);

        // SMTP ENCRYPTION
        $option = $options->where("code", "MAILER_ENCRYPTION")->first();
        // None = "", SSL = "ssl", TLS = "tls"
        self::$smtpEncryption = self::$smtpTransport === "gmail" ? "tls" : ($option ? $option->getValue() : null);

        // SMTP AUTHENTICATION ( None = "", Plain = "plain", Login = "login", CRAM-MD5 = "cram-md5" )
        $option = $options->where("code", "MAILER_AUTH_MODE")->first();
        self::$smtpAuthentication = self::$smtpTransport === "gmail" ? "login" : ($option ? $option->getValue() : null);

        // SMTP VERIFY SSL CERTIFICATE?
        $option = $options->where("code", "MAILER_VERIFY_SSL_CERTIFICATES")->first();
        self::$smtpVerifySslCertificate = $option ? (bool)$option->getValue() : null;

        // SMTP SENDER ADDRESS
        $option = $options->where("code", "MAILER_SENDER_ADDRESS")->first();
        self::$smtpSenderEmail = $option ? $option->getValue() : null;

        /*
        if(Plugin::hasModule(Plugin::MODULE_SMTP) && (
                self::$smtpTransport === null ||
                self::$smtpUsername === null ||
                self::$smtpPassword === null ||
                self::$smtpHost === null ||
                self::$smtpPort === null ||
                self::$smtpEncryption === null ||
                self::$smtpAuthentication === null ||
                self::$smtpVerifySslCertificate === null ||
                self::$smtpSenderEmail === null
            ))
        {
            // THEN display the requirement and exit!
            echo "
                <p>This Plugin uses the SMTP module and your UCRM SMTP Settings have not been configured!</p>
                <p>Some things to check:</p>
                <ul>
                    <li>
                        <!--suppress HtmlUnknownTarget -->
                        <a href='/system/settings/mailer' target='_parent'>SMTP Configuration</a> has not been set?
                    </li>
                </ul>
            ";

            Log::write("This plugin uses the SMTP module, which requires that the 'SMTP Configuration' be completed in System -> Settings -> Mailer.", "SETTINGS");
            exit();
        }
        */

        // GOOGLE API KEY
        $option = $options->where("code", "GOOGLE_API_KEY")->first();
        self::$googleApiKey = $option->getValue();

        // TIMEZONE
        $option = $options->where("code", "APP_TIMEZONE")->first();
        self::$timezone = $option->getValue();

        // SERVER IP
        $option = $options->where("code", "SERVER_IP")->first();
        self::$serverIP = $option->getValue();

        // SERVER FQDN
        $option = $options->where("code", "SERVER_FQDN")->first();
        self::$serverFQDN = $option ? $option->getValue() : null;

        // SERVER PORT
        $option = $options->where("code", "SERVER_PORT")->first();
        self::$serverPort = $option ? (int)$option->getValue() : null;

        $properties = get_class_vars(PluginConfig::class);
        if($properties["smtpPassword"] !== null)
            $properties["smtpPassword"] = str_repeat("*", strlen($properties["smtpPassword"]));

        if(class_exists("\\UCRM\\Plugins\\Settings") && call_user_func("\\UCRM\\Plugins\\Settings::getVerboseLogging"))
            Log::info("CONFIGURATION: ".json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return true;
    }

}
