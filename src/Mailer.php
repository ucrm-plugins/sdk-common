<?php /** @noinspection PhpUndefinedClassInspection, PhpUnused, SpellCheckingInspection */
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK;

use Dotenv\Dotenv;

use SpaethTech\UCRM\SDK\Exceptions\DatabaseConnectionException;
use SpaethTech\UCRM\SDK\Exceptions\ModelClassException;
use SpaethTech\UCRM\SDK\Dynamics\AutoObject;

use SpaethTech\UCRM\SDK\Data\Database;

use Exception;
use ReflectionException;

use App\Settings;

/**
 * Class Mailer
 *
 * @package MVQN\UCRM\Plugins
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 *
 * @method static string      getType()
 * @method static string|null getGmailPassword()
 * @method static string|null getGmailUsername()
 * @method static string|null getCustomSmtpPort()
 * @method static string|null getCustomSmtpSender()
 * @method static string|null getCustomSmtpHostname()
 * @method static string|null getCustomSmtpPassword()
 * @method static string|null getCustomSmtpUsername()
 * @method static bool        getTlsAllowUnauthorized()
 * @method static bool        getCustomSmtpAuthEnabled()
 * @method static string      getCustomSmtpSecurityMode()
 */
final class Mailer extends AutoObject
{
    #region CONSTANTS

    /** @var string SMTP Server Type:           No SMTP         */
    public const MAILER_TYPE_NOSMTP             = "nosmtp";
    /** @var string SMTP Server Type:           Gmail           */
    public const MAILER_TYPE_GMAIL              = "gmail";
    /** @var string SMTP Server Type:           Custom SMTP     */
    public const MAILER_TYPE_CUSTOM             = "smtp";

    /** @var string SMTP Server Security Mode:  Plain text      */
    public const MAILER_SECURITY_MODE_PLAIN     = "Plain text";
    /** @var string SMTP Server Security Mode:  SSL             */
    public const MAILER_SECURITY_MODE_SSL       = "SSL";
    /** @var string SMTP Server Security Mode:  TLS             */
    public const MAILER_SECURITY_MODE_TLS       = "TLS";

    #endregion

    #region PROPERTIES

    /** @var string */
    protected static $type = self::MAILER_TYPE_NOSMTP;

    /** @var string */
    protected static $gmailPassword;

    /** @var string */
    protected static $gmailUsername;

    /** @var string */
    protected static $customSmtpPort;

    /** @var string */
    protected static $customSmtpSender;

    /** @var string */
    protected static $customSmtpHostname;

    /** @var string */
    protected static $customSmtpPassword;

    /** @var string */
    protected static $customSmtpUsername;

    /** @var bool */
    protected static $tlsAllowUnauthorized = false;

    /** @var bool */
    protected static $customSmtpAuthEnabled = false;

    /** @var string */
    protected static $customSmtpSecurityMode = self::MAILER_SECURITY_MODE_PLAIN;

    #endregion

    #region AUTO-OBJECT

    /**
     * Executes prior to the very fist static __call() method and used to initialize the properties of this class.
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws Exceptions\PluginNotInitializedException
     * @throws ModelClassException
     * @throws ReflectionException
     * @throws Exception
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

        Database::connect(
            getenv("POSTGRES_HOST")         ?: Settings::UCRM_DB_HOST,
            (int)getenv("POSTGRES_PORT")    ?: Settings::UCRM_DB_PORT,
            getenv("POSTGRES_DB")           ?: Settings::UCRM_DB_NAME,
            getenv("POSTGRES_USER")         ?: Settings::UCRM_DB_USER,
            getenv("POSTGRES_PASSWORD")     ?: Settings::UCRM_DB_PASSWORD
        );

        // Get a collection of all rows of the option table from the database!
        $settings = Setting::select();

        /** @var Setting $setting */
        $setting = $settings->where("name", "smtp")->first();
        $smtp = $setting->getValue();

        $json = json_decode($smtp, true);

        /** @noinspection PhpStatementHasEmptyBodyInspection */
        if(json_last_error() !== JSON_ERROR_NONE)
        {
            // Error handling?
        }

        self::$type                     = $json["type"];
        self::$gmailPassword            = $json["gmailPassword"];
        self::$gmailUsername            = $json["gmailUsername"];
        self::$customSmtpPort           = $json["customSmtpPort"];
        self::$customSmtpSender         = $json["customSmtpSender"];
        self::$customSmtpHostname       = $json["customSmtpHostname"];
        self::$customSmtpPassword       = $json["customSmtpPassword"];
        self::$customSmtpUsername       = $json["customSmtpUsername"];
        self::$tlsAllowUnauthorized     = $json["tlsAllowUnauthorized"];
        self::$customSmtpAuthEnabled    = $json["customSmtpAuthEnabled"];
        self::$customSmtpSecurityMode   = $json["customSmtpSecurityMode"];

        $properties = get_class_vars(Setting::class);

        if(class_exists("\\UCRM\\Plugins\\Settings") && call_user_func("\\UCRM\\Plugins\\Settings::getVerboseLogging"))
            Log::info("MAILER: ".json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return true;
    }

    #endregion
}
