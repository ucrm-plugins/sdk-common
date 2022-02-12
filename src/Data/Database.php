<?php
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK\Data;

use SpaethTech\UCRM\SDK\Support\Strings;
use Exception;

/**
 * Class Database
 *
 * @package MVQN\Data
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @final
 */
final class Database
{
    // =================================================================================================================
    // PROPERTIES
    // =================================================================================================================
    
    /** @var string|null The database hostname. */
    private static $databaseHost;
    
    /** @var int|null The database port number. */
    private static $databasePort;
    
    /** @var string|null The database username. */
    private static $databaseUser;
    
    /** @var string|null The database password. */
    private static $databasePass;
    
    /** @var string|null The database name. */
    private static $databaseName;
    
    /** @var \PDO|null The database object. */
    private static $pdo;
    
    /** @var string[] */
    private static $schemas;
    
    // =================================================================================================================
    // METHODS: CONNECTION
    // =================================================================================================================
    
    /**
     * Attempts a connection to the database or simply returns an existing connection unless otherwise requested.
     *
     * @param string $host The host name where the database exists.
     * @param int $port The port number to which the database connection should be made.
     * @param string $dbname The database name.
     * @param string $user The username with access to the database.
     * @param string $pass The password for the provided username.
     * @param bool $reconnect If TRUE, then forces a new database (re-)connection to be made, defaults to FALSE.
     * @return null|\PDO Returns a valid database object for use with future database commands.
     * @throws Exceptions\DatabaseConnectionException
     */
    public static function connect(string $host = "", int $port = 0, string $dbname = "", string $user = "",
        string $pass = "", bool $reconnect = false): ?\PDO
    {
        // IF the connection already exists AND a reconnect was not requested...
        if(self::$pdo !== null && !$reconnect)
            // THEN return the current database object!
            return self::$pdo;
        
        // IF no hostname was provided AND hostname was not previously set, THEN throw an Exception!
        if($host === "" && (self::$databaseHost === null || self::$databaseHost === ""))
            throw new Exceptions\DatabaseConnectionException("A valid host name was not provided!");
        // OTHERWISE, set the hostname to the one provided or the previous one if none was provided.
        self::$databaseHost = $host = $host ?: self::$databaseHost;
        
        // IF no port number was provided AND port number was not previously set, THEN throw an Exception!
        if($port === 0 && (self::$databasePort === null || self::$databasePort === 0))
            throw new Exceptions\DatabaseConnectionException("A valid port number was not provided!");
        // OTHERWISE, set the port number to the one provided or the previous one if none was provided.
        self::$databasePort = $port = $port ?: self::$databasePort;
        
        // IF no database name was provided AND database name was not previously set, THEN throw an Exception!
        if($dbname === "" && (self::$databaseName === null || self::$databaseName === ""))
            throw new Exceptions\DatabaseConnectionException("A valid database name was not provided!");
        // OTHERWISE, set the database name to the one provided or the previous one if none was provided.
        self::$databaseName = $dbname = $dbname ?: self::$databaseName;
        
        // IF no username was provided AND username was not previously set, THEN throw an Exception!
        if($user === "" && (self::$databaseUser === null || self::$databaseUser === ""))
            throw new Exceptions\DatabaseConnectionException("A valid username was not provided!");
        // OTHERWISE, set the username to the one provided or the previous one if none was provided.
        self::$databaseUser = $user = $user ?: self::$databaseUser;
        
        // IF no password was provided AND password was not previously set, THEN throw an Exception!
        if($pass === "" && (self::$databasePass === null || self::$databasePass === ""))
            throw new Exceptions\DatabaseConnectionException("A valid password was not provided!");
        // OTHERWISE, set the password to the one provided or the previous one if none was provided.
        self::$databasePass = $pass = $pass ?: self::$databasePass;
        
        // All pre-checks should have ensured a valid state for connection!
        
        try
        {
            // Attempt to create a new database connection using the provided information.
            self::$pdo = new \PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
                // Setting some default options.
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
            
            self::$schemas = array_map(
                function($schema)
                {
                    return $schema["schema_name"];
                },
                self::$pdo->query("SELECT schema_name FROM information_schema.schemata")->fetchAll()
            );
            
            // IF the connection is valid, return the new database object!
            if(self::$pdo)
                return self::$pdo;
        }
        catch(\PDOException $e)
        {
            // OTHERWISE, throw an Exception!
            throw new Exceptions\DatabaseConnectionException($e->getMessage());
        }
        
        // We should NEVER reach this line of code, but if we somehow do, return NULL!
        return null;
    }
    
    // =================================================================================================================
    // METHODS: QUERYING
    // =================================================================================================================
    
    public static function query(string $query, array $params = []): array
    {
        // Get a connection to the database.
        $pdo = self::connect();
        
        // Generate a SQL statement, given the provided parameters.
        $sql = $pdo->prepare($query);
        $sql->execute($params);
        
        // Execute the query.
        $results = $sql->fetchAll();
        
        // Return the results!
        return $results;
    }
    
    /**
     * Issues a SELECT query to the database.
     *
     * @param string $table The table for which to make the query.
     * @param array $columns An optional array of column names to be returned.
     * @param string $orderBy An optional ORDER BY suffix for sorting.
     * @return array Returns an associative array of rows from the database.
     * @throws Exceptions\DatabaseConnectionException
     */
    public static function select(string $table, array $columns = [], string $orderBy = ""): array
    {
        // Get a connection to the database.
        $pdo = self::connect();
        
        list($database, $schema, $table) = array_values(self::parseTable($table));
        
        //if($schema !== "")
        //    $pdo->exec("SET search_path TO $schema");
        
        // Generate a SQL statement, given the provided parameters.
        $sql =
            "SELECT ".($columns === [] ? "*" : "\"".implode("\", \"", $columns)."\"")." FROM ".($schema !== "" ? "\"$schema\"." : "")."\"$table\"".
            ($orderBy !== "" ? " ORDER BY $orderBy" : "");
        
        // Execute the query.
        $results = $pdo->query($sql)->fetchAll();
        
        // Return the results!
        return $results;
    }
    
    private static function parseTable(string $table): array
    {
        $results = [
            "database"  =>  "",
            "schema"    =>  "",
            "table"     =>  $table,
        ];
        
        if(Strings::contains($table, "."))
        {
            $_database  = "";
            $_schema    = "";
            $_table     = "";
            
            switch(count($parts = explode(".", $table)))
            {
                case 1  :   list($_table)                       = $parts;   break;
                case 2  :   list($_schema, $_table)             = $parts;   break;
                case 3  :   list($_database, $_schema, $_table) = $parts;   break;
                
                default :   throw new Exception("Invalid '[[database.]schema.]table' format!");
            }
            
            if($_database !== "" && $_database !== self::$databaseName)
            {
                throw new Exception(
                    "Invalid database reference '$_database' in the table syntax '$table', as this PDO is already ".
                    "connected to the '".self::$databaseName."' database and would need to be re-created!"
                );
            }
            
            if($_schema !== "" && !in_array($_schema, self::$schemas))
            {
                throw new Exception(
                    "Invalid schema reference '$_schema' in the table syntax '$table', as the schema does not exist ".
                    "in the '".self::$databaseName."' database!"
                );
            }
            
            $results = [
                "database"  =>  $_database,
                "schema"    =>  $_schema,
                "table"     =>  $_table,
            ];
            
        }
        
        return $results;
        
    }
    
    
    /**
     * Issues a SELECT/WHERE query to the database.
     *
     * @param string $table The table for which to make the query.
     * @param string $where An optional WHERE clause to use for matching, when omitted a SELECT query is made instead.
     * @param array $columns An optional array of column names to be returned.
     * @param string $orderBy An optional ORDER BY suffix for sorting.
     * @return array Returns an associative array of matching rows from the database.
     * @throws Exceptions\DatabaseConnectionException
     */
    public static function where(string $table, string $where = "", array $columns = [], string $orderBy = ""): array
    {
        // Get a connection to the database.
        $pdo = self::connect();
        
        list($database, $schema, $table) = array_values(self::parseTable($table));
        
        //if($schema !== "")
        //    $pdo->exec("SET search_path TO $schema");
        
        // Generate a SQL statement, given the provided parameters.
        $sql =
            "SELECT ".($columns === [] ? "*" : "\"".implode("\", \"", $columns)."\"")." FROM ".($schema !== "" ? "\"$schema\"." : "")."\"$table\"".
            ($where !== "" ? " WHERE $where"  : "").
            ($orderBy !== "" ? " ORDER BY $orderBy" : "");
        
        // Execute the query and return the results!
        return $pdo->query($sql)->fetchAll();
    }
    
    
    
    
    
    public static function insert(string $table, array $values, array $columns = []): int
    {
        // Get a connection to the database.
        $pdo = self::connect();
        
        list($database, $schema, $table) = array_values(self::parseTable($table));
        
        if($schema !== "")
            $pdo->exec("SET search_path TO $schema");
        
        // Generate a SQL statement, given the provided parameters.
        $sql =
            "INSERT INTO \"$table\" (".($columns === [] ?
                "\"".implode("\", \"", array_keys($values))."\"" : "\"".implode("\", \"", $columns)."\"").") VALUES (";
        
        $vals = [];
        
        foreach($values as $column => $value)
        {
            if($columns !== [] && !in_array($column, $columns))
                continue;
            
            $vals[] = $value;
            
        }
        
        $sql .= "'".implode("', '", $vals)."');";
        
        // Execute the query.
        $results = $pdo->exec($sql);//  prepare($sql)->execute();
        
        if($results === false && count($pdo->errorInfo()) >= 3)
            echo $pdo->errorInfo()[2]."\n";
        
        // Return the results!
        return $results === false ? 0 : $results;
    }
    
    
    
    
    public static function delete(string $table, string $where): int
    {
        // Get a connection to the database.
        $pdo = self::connect();
        
        list($database, $schema, $table) = array_values(self::parseTable($table));
        
        //if($schema !== "")
        //    $pdo->exec("SET search_path TO $schema");
        
        // Generate a SQL statement, given the provided parameters.
        $sql =
            "DELETE FROM ".($schema !== "" ? "\"$schema\"." : "")."\"$table\"".
            ($where !== "" ? " WHERE $where" : "");
        
        // Execute the query.
        $results = $pdo->exec($sql);//  prepare($sql)->execute();
        
        if($results === false && count($pdo->errorInfo()) >= 3)
            echo $pdo->errorInfo()[2]."\n";
        
        // Return the results!
        return $results === false ? 0 : $results;
    }
    
    
    /*
    public static function schema(string $schema)
    {
        // Get a connection to the database.
        $pdo = self::connect();

        $pdo->exec("SET search_path TO $schema");
    }
    */
    
}