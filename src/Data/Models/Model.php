<?php
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK\Data\Models;

use Exception;
use SpaethTech\UCRM\SDK\Annotations\AnnotationReader;
use SpaethTech\UCRM\SDK\Collections\Collection;
use SpaethTech\UCRM\SDK\Support\Casings;
use SpaethTech\UCRM\SDK\Exceptions\ModelCreationException;
use SpaethTech\UCRM\SDK\Dynamics\AutoObject;
use SpaethTech\UCRM\SDK\Data\Database;

use SpaethTech\UCRM\SDK\Exceptions\DatabaseConnectionException;
use SpaethTech\UCRM\SDK\Exceptions\ModelClassException;
use SpaethTech\UCRM\SDK\Exceptions\ModelMissingPropertyException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;

/**
 * Class Model
 *
 * @package MVQN\UCRM\Data
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @final
 */
abstract class Model extends AutoObject
{
    // =================================================================================================================
    // PROPERTIES
    // =================================================================================================================
    
    /** @var array|null A cache for storing each Model's column => property pairings. */
    private static $columnPropertiesCache;
    
    /** @var array|null A cache for storing each Model's class => table name pairings. */
    private static $tableNameCache;
    
    
    
    /** @var array|null A cache for storing each Model's table name => primary key pairings. */
    private static $primaryKeyCache;
    
    /** @var array|null A cache for storing each Model's table name => foreign key pairings. */
    private static $foreignKeysCache;
    
    /** @var array|null A cache for storing each Model's column name => nullability pairings. */
    private static $nullablesCache;
    
    /** @var array|null A cache for storing each Model's column name => column pairings. */
    private static $columnsCache;
    
    // =================================================================================================================
    // CONSTRUCTOR
    // =================================================================================================================
    
    /**
     * Model constructor.
     *
     * @param array $data An associative array of column name => value pairs from the database.
     * @throws ModelClassException
     * @throws ModelMissingPropertyException
     * @throws \ReflectionException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        
        // Get this class name.
        $class = get_class($this);
        
        // IF the column => property cache has not yet been built, or does not exist for this class...
        if (self::$columnPropertiesCache === null || !array_key_exists($class, self::$columnPropertiesCache) ||
            self::$columnPropertiesCache[$class] === null)
            // THEN build it now!
            self::buildColumnPropertiesCache();
        
        // Loop through each column name => value pair provided...
        foreach($data as $name => $value)
        {
            // IF the column name has a matching index in the column => property cache for this class...
            if(array_key_exists($name, self::$columnPropertiesCache[$class]))
            {
                // THEN set the current column's value on all properties annotated with this column name.
                foreach(self::$columnPropertiesCache[$class][$name] as $propertyName)
                    $this->$propertyName = $value;
            }
            else
            {
                // OTHERWISE no matching column => property pairing or @ColumnNameAnnotation was found!
                
                $test = self::$columnPropertiesCache;
                
                throw new ModelMissingPropertyException("Could not find a property '$name' of class '$class'.  ".
                    "Are you missing a '@ColumnNameAnnotation' on a property?");
            }
        }
    }
    
    // =================================================================================================================
    // HELPERS
    // =================================================================================================================
    
    
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Builds a column => property cache for the calling class.
     *
     * @throws ModelClassException
     * @throws \ReflectionException
     */
    private static function buildColumnPropertiesCache()
    {
        // Get the calling child class, ensuring that the Model class was not used!
        $class = self::getStaticChildClass();
        
        // Create an AnnotationReader and get all of the annotations on properties of this class.
        $annotations = new AnnotationReader($class);
        $properties = $annotations->getPropertyAnnotations();
        
        // Initialize a collection of column => property names.
        self::$columnPropertiesCache[$class] = [];
        
        // Loop through each property annotation...
        foreach($properties as $property)
        {
            // Skip non-annotated properties!
            if($property === [])
                continue;
            
            // If the current property has a @ColumnNameAnnotation...
            if(array_key_exists("ColumnName", $property))
                // THEN add the column name and property name pairing to the collection!
                self::$columnPropertiesCache[$class][$property["ColumnName"]][] = $property["var"]["name"];
            else
                // OTHERWISE add the property name to the collection, paired to itself!
                self::$columnPropertiesCache[$class][$property["var"]["name"]][] = $property["var"]["name"];
        }
    }
    
    /**
     * Compares the provided name to the column => property cache to ensure that both column and property names can be
     * used in functions requiring only the column name.
     *
     * @param string $name The name of the column or property of which to lookup.
     * @return string|null Returns the column name for the column or property name specified.
     * @throws ModelClassException
     * @throws \ReflectionException
     */
    private static function getColumnName(string $name): ?string
    {
        // Get the calling child class, ensuring that the Model class was not used!
        $class = self::getStaticChildClass();
        
        // IF the column => property cache has not yet been built, or does not exist for this class...
        if (self::$columnPropertiesCache === null || !array_key_exists($class, self::$columnPropertiesCache) ||
            self::$columnPropertiesCache[$class] === null)
            // THEN build it now!
            self::buildColumnPropertiesCache();
        
        // IF the name exists as a column => property cache key...
        if(array_key_exists($name, self::$columnPropertiesCache[$class]))
            // THEN return the column name as is!
            return $name;
        
        // OTHERWISE, we need to loop through all of the column => property pairings in the cache...
        foreach(self::$columnPropertiesCache[$class] as $column => $properties)
        {
            // IF the current column name is associated with a property matching the name provided...
            if(in_array($name, $properties))
                // THEN return the current column name.
                return $column;
        }
        
        // Nothing was matched, return NULL!
        return null;
    }
    
    /**
     * @return string
     * @throws ModelClassException
     * @throws \ReflectionException
     */
    private static function getTableName(): string
    {
        // Get the calling child class, ensuring that the Model class was not used!
        $class = self::getStaticChildClass();
        
        // IF the class => table name cache has already been built for this class...
        if(self::$tableNameCache !== null && array_key_exists($class, self::$tableNameCache))
            // THEN return the cached table name!
            return self::$tableNameCache[$class];
        
        // Create an AnnotationReader and get all of the annotations on properties of this class.
        $annotations = new AnnotationReader($class);
        
        // IF the child class has a @TableNameAnnotation...
        if($annotations->hasClassAnnotation("TableName"))
        {
            // Get the table name from the @TableNameAnnotation.
            $tableName = $annotations->getClassAnnotation("TableName");
            
            // Cache the class => table name.
            self::$tableNameCache[$class] = $tableName;
        }
        else
        {
            // OTHERWISE, attempt some auto conversions...
            
            // Get the class name without the namespace.
            $short = $annotations->getReflectedClass()->getShortName();
            
            // Add the snake_case form of the class name to the cache.
            self::$tableNameCache[$class] = Casings::pascal2snake($short);
        }
        
        // THEN simply return that as the table name!
        return self::$tableNameCache[$class];
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Gets the calling child class name or throws a ModelClassException if Model was used directly!
     *
     * @return string Returns the child class name.
     * @throws ModelClassException
     */
    private static function getStaticChildClass(): string
    {
        // Get the calling class.
        $class = get_called_class();
        
        // IF the calling class is Model...
        if($class === __CLASS__)
            // THEN throw an Exception!
            throw new ModelClassException("The Model class cannot be used directly, as it is abstract!");
        
        // OTHERWISE, it is a child class, so return the class name!
        return $class;
    }
    
    private static function parseTable(string $table): array
    {
        $results = [
            "database"  =>  "",
            "schema"    =>  "",
            "table"     =>  $table,
        ];
        
        if(strpos($table, ".") !== false)
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
            
            /*
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
            */
            
            $results = [
                "database"  =>  $_database,
                "schema"    =>  $_schema,
                "table"     =>  $_table,
            ];
            
        }
        
        return $results;
        
    }
    
    
    
    // =================================================================================================================
    // METHODS: QUERYING
    // =================================================================================================================
    
    /**
     * Selects all rows from the database as a Collection of Model class objects.
     *
     * @return Collection Returns a Collection of Model objects with populated data from the database.
     * @throws DatabaseConnectionException
     * @throws ModelClassException
     * @throws \ReflectionException
     */
    public static function select(): Collection
    {
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        // Get the calling child class, ensuring that the Model class was not used!
        $class = self::getStaticChildClass();
        
        // Get the table name from either a @TableNameAnnotation or an automated conversion from the class name...
        $tableName = self::getTableName();
        
        list($database, $schema, $table) = array_values(self::parseTable($tableName));
        
        // Build the SQL statement.
        $sql = "SELECT * FROM ".($schema !== "" ? "\"$schema\"." : "")."\"$table\"";
        
        // Fetch the results from the database.
        $results = $pdo->query($sql)->fetchAll();
        
        // Create a new Collection to store the converted objects.
        $collection = new Collection($class, []);
        
        // Loop through each result...
        foreach($results as $result)
        {
            // Create a new object and populate it's properties.
            $object = new $class($result);
            
            // Append the new object to the collection.
            $collection->push($object);
        }
        
        // Finally, return the Collection!
        return $collection;
    }
    
    /**
     * Selects only the matching rows from the database as a Collection of Model class objects.
     *
     * @param string $column The column name on which to compare.
     * @param string $operator The operator to use for comparison.
     * @param mixed $value The value on which to compare.
     * @return Collection Returns a Collection of Model objects with populated data from the database.
     * @throws DatabaseConnectionException
     * @throws ModelClassException
     * @throws ModelMissingPropertyException
     * @throws \ReflectionException
     */
    public static function where(string $column, string $operator, $value): Collection
    {
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        // Get the calling child class, ensuring that the Model class was not used!
        $class = self::getStaticChildClass();
        
        // Get the table name from either a @TableNameAnnotation or an automated conversion from the class name...
        $tableName = self::getTableName();
        
        // Lookup the correct column name.
        $column = self::getColumnName($column);
        
        // IF no matching column name could be determined, THEN throw an Exception!
        if($column === null)
            throw new ModelMissingPropertyException("Could not find a property '$column' of class '$class'.  ".
                "Are you missing a '@ColumnNameAnnotation' on a property?");
        
        list($database, $schema, $table) = array_values(self::parseTable($tableName));
        
        // Build the SQL statement.
        $sql = "SELECT * FROM ".($schema !== "" ? "\"$schema\"." : "")."\"$table\" WHERE \"$column\" $operator ".
            (gettype($value) === "string" ? "'$value'" : "$value");
        
        // Fetch the results from the database.
        $results = $pdo->query($sql)->fetchAll();
        
        // Create a new Collection to store the converted objects.
        $collection = new Collection($class, []);
        
        // Loop through each result...
        foreach($results as $result)
        {
            // Create a new object and populate it's properties.
            $object = new $class($result);
            
            // Append the new object to the collection.
            $collection->push($object);
        }
        
        // Finally, return the Collection!
        return $collection;
    }
    
    
    public function insert(array $columns = []): self
    {
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        // Get the calling child class, ensuring that the Model class was not used!
        $class = self::getStaticChildClass();
        
        // Get the table name from either a @TableNameAnnotation or an automated conversion from the class name...
        $tableName = self::getTableName();
        
        //$annotationReader = new AnnotationReader($class);
        
        $nullables = self::getNullableNames($tableName);
        $primaryKey = self::getPrimaryKeyName($tableName);
        
        $values = [];
        
        foreach(get_object_vars($this) as $property => $value)
        {
            // Lookup the correct column name.
            $column = self::getColumnName($property);
            
            if($columns !== [] && in_array($column, $columns))
                continue;
            
            if($column === $primaryKey)
                continue;
            
            if(!in_array($column, $nullables) && $value === null)
                throw new Exception("Column '$column' is set as NOT NULL and currently contains a NULL value!");
            
            $values[$column] = $value;
        }
        
        list($database, $schema, $table) = array_values(self::parseTable($tableName));
        
        // Build the SQL statement.
        $sql = "INSERT INTO ".($schema !== "" ? "\"$schema\"." : "")."\"$table\" ".("(\"".implode("\", \"", array_keys($values))."\")")." VALUES ";
        
        $vals = [];
        
        foreach($values as $column => $value)
        {
            switch(gettype($value))
            {
                case "string":
                    $vals[] = "'$value'";
                    break;
                
                case "boolean":
                    $vals[] = ($value ? "TRUE" : "FALSE");
                    break;
                
                default:
                    $vals[] = ($value === null ? "NULL" : "$value");
                    break;
            }
            
            //$vals[] = (gettype($value) === "string" ?  : ($value === null ? "NULL" : "$value"));
        }
        
        $sql .= "(".implode(", ", $vals).");";
        
        // Fetch the results from the database.
        $results = $pdo->exec($sql);
        
        if($results === false && count($pdo->errorInfo()) >= 3)
            throw new Exception($pdo->errorInfo()[2], $pdo->errorInfo()[1]);
        
        $id = $pdo->lastInsertId();
        /** @var Model $last */
        $last = self::where($primaryKey, "=", $id)->first();
        
        return $last;
    }
    
    
    
    public function update(array $columns = []): self
    {
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        // Get the calling child class, ensuring that the Model class was not used!
        $class = self::getStaticChildClass();
        
        // Get the table name from either a @TableNameAnnotation or an automated conversion from the class name...
        $tableName = self::getTableName();
        
        //$annotationReader = new AnnotationReader($class);
        
        $nullables = self::getNullableNames($tableName);
        $primaryKey = self::getPrimaryKeyName($tableName);
        $primaryKeyProperty = self::$columnPropertiesCache[$class][$primaryKey][0];
        
        if($this->$primaryKeyProperty === null)
            throw new Exception("Primary Key must not be NULL when using 'Model::insert()'!");
        
        $values = [];
        
        foreach(get_object_vars($this) as $property => $value)
        {
            // Lookup the correct column name.
            $column = self::getColumnName($property);
            
            if($columns !== [] && in_array($column, $columns))
                continue;
            
            if($column === $primaryKey)
                continue;
            
            if(!in_array($column, $nullables) && $value === null)
                throw new Exception("Column '$column' is set as NOT NULL and currently contains a NULL value!");
            
            $values[$column] = $value;
        }
        
        list($database, $schema, $table) = array_values(self::parseTable($tableName));
        
        // Build the SQL statement.
        $sql = "UPDATE ".($schema !== "" ? "\"$schema\"." : "")."\"$table\" ";
        
        $vals = [];
        
        foreach($values as $column => $value)
        {
            switch(gettype($value))
            {
                case "string":
                    $vals[] = "\"$column\" = '$value'";
                    break;
                
                case "boolean":
                    $vals[] = "\"$column\" = ".($value ? "TRUE" : "FALSE");
                    break;
                
                default:
                    $vals[] = "\"$column\" = ".($value === null ? "NULL" : "$value");
                    break;
            }
        }
        
        
        
        $sql .= "SET ".implode(", ", $vals)." WHERE $primaryKey = {$this->$primaryKeyProperty}";
        
        // Fetch the results from the database.
        $results = $pdo->exec($sql);
        
        if($results === false && count($pdo->errorInfo()) >= 3)
            throw new Exception($pdo->errorInfo()[2], $pdo->errorInfo()[1]);
        
        $id = $this->$primaryKeyProperty;
        /** @var Model $last */
        $last = self::where($primaryKey, "=", $id)->first();
        
        return $last;
    }
    
    
    // =================================================================================================================
    // METHODS: SCHEMA
    // =================================================================================================================
    
    /**
     * Gets the PRIMARY KEY for the specified table.
     *
     * @param string $table The table name to inspect.
     * @return array Returns an array of information pertaining to the PRIMARY KEY of the specified table.
     * @throws DatabaseConnectionException
     */
    private static function getPrimaryKey(string $table): array
    {
        if (self::$primaryKeyCache !== null && array_key_exists($table, self::$primaryKeyCache))
            return self::$primaryKeyCache[$table];
        
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        /** @noinspection SqlResolve */
        $query = "
            SELECT
                tc.constraint_name, tc.table_name, kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
            WHERE constraint_type = 'PRIMARY KEY' AND tc.table_name = '$table'
        ";
        
        $results = $pdo->query($query);
        self::$primaryKeyCache[$table] = $results->fetch(); // ONLY ONE PRIMARY KEY!
        
        return self::$primaryKeyCache[$table];
    }
    
    /**
     * Gets the column name of the PRIMARY KEY for the specified table.
     *
     * @param string $table The table name to inspect.
     * @return string Returns the column name of the PRIMARY KEY of the specified table.
     * @throws DatabaseConnectionException
     */
    private static function getPrimaryKeyName(string $table): string
    {
        return self::getPrimaryKey($table)["column_name"];
    }
    
    /**
     * Checks to see if the specified column name for the specified table is a PRIMARY KEY.
     *
     * @param string $table The table name to inspect.
     * @param string $column The column name to inspect.
     * @return bool Returns TRUE if the specified column of the specified table is a PRIMARY KEY, otherwise FALSE.
     * @throws DatabaseConnectionException
     */
    private static function isPrimaryKey(string $table, string $column): bool
    {
        return self::getPrimaryKey($table)["column_name"] === $column;
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Gets an array of FOREIGN KEY columns for the specified table.
     *
     * @param string $table The table name to inspect.
     * @return array Returns an array of information pertaining to the FOREIGN KEYs of the specified table.
     * @throws DatabaseConnectionException
     */
    private static function getForeignKeys(string $table): array
    {
        if (self::$foreignKeysCache !== null && array_key_exists($table, self::$foreignKeysCache))
            return self::$foreignKeysCache[$table];
        
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        /** @noinspection SqlResolve */
        $query = "
            SELECT
                tc.constraint_name, tc.table_name, kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM
                information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                  ON tc.constraint_name = kcu.constraint_name
                JOIN information_schema.constraint_column_usage AS ccu
                  ON ccu.constraint_name = tc.constraint_name
            WHERE constraint_type = 'FOREIGN KEY' AND tc.table_name = '$table'
        ";
        
        self::$foreignKeysCache[$table] = [];
        
        $rows = $pdo->query($query);
        while($row = $rows->fetch())
            self::$foreignKeysCache[$table][$row["column_name"]] = $row;
        
        return self::$foreignKeysCache[$table];
    }
    
    /**
     * Get an array of the columns names of all FOREIGN KEY columns for the specified table.
     *
     * @param string $table The table name to inspect.
     * @return array Returns an array of the column names of all FOREIGN KEYs of the specified table.
     * @throws DatabaseConnectionException
     */
    private static function getForeignKeysNames(string $table): array
    {
        if (self::$foreignKeysCache === null || !array_key_exists($table, self::$foreignKeysCache))
            self::getForeignKeys($table);
        
        return array_keys(self::$foreignKeysCache[$table]);
    }
    
    /**
     * Checks to see if the specified column name for the specified table is a FOREIGN KEY.
     *
     * @param string $table The table name to inspect.
     * @param string $column The column name to inspect.
     * @return bool Returns TRUE if the specified column of the specified table is a FOREIGN KEY, otherwise FALSE.
     * @throws DatabaseConnectionException
     */
    private static function isForeignKey(string $table, string $column): bool
    {
        return array_key_exists($column, self::getForeignKeys($table));
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Gets all of the NULL-able columns from the specified table schema.
     *
     * @param string $table The name of the table for which to inspect.
     * @return array Returns an associative array of columns that are NULL-able.
     * @throws DatabaseConnectionException
     */
    private static function getNullables(string $table): array
    {
        if (self::$nullablesCache !== null && array_key_exists($table, self::$nullablesCache))
            return self::$nullablesCache[$table];
        
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        /** @noinspection SqlResolve */
        $query = "
            SELECT
                column_name, data_type, is_nullable, column_default
            FROM
                information_schema.columns
            WHERE table_name = '$table' AND is_nullable = 'YES'
        ";
        
        self::$nullablesCache[$table] = [];
        
        $rows = $pdo->query($query);
        while($row = $rows->fetch())
            self::$nullablesCache[$table][$row["column_name"]] = $row;
        
        return self::$nullablesCache[$table];
    }
    
    /**
     * Gets all of the names of NULL-able columns from the specified table schema.
     *
     * @param string $table
     * @return array
     * @throws DatabaseConnectionException
     */
    private static function getNullableNames(string $table): array
    {
        if (self::$nullablesCache === null || !array_key_exists($table, self::$nullablesCache))
            self::getNullables($table);
        
        return array_keys(self::$nullablesCache[$table]);
    }
    
    /**
     * Gets the NULL-ability of a column from the specified table schema.
     *
     * @param string $table The name of the table for which to inspect.
     * @param string $column The name of the column for which to check.
     * @return bool Returns TRUE if the column is NULL-able, otherwise FALSE.
     * @throws DatabaseConnectionException
     */
    private static function isNullable(string $table, string $column): bool
    {
        // IF the nullables cache is not already built, THEN build it!
        if (self::$nullablesCache === null || !array_key_exists($table, self::$nullablesCache))
            self::getNullables($table);
        
        // Return TRUE if the column is included in the nullables cache!
        return array_key_exists($column, self::$nullablesCache[$table]);
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Gets all of the columns from the specified table schema.
     *
     * @param string $table The name of the table for which to inspect.
     * @return array Returns an associative array of column name => column schema data.
     * @throws DatabaseConnectionException
     */
    private static function getColumns(string $table): array
    {
        if (self::$columnsCache !== null && array_key_exists($table, self::$columnsCache))
            return self::$columnsCache[$table];
        
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        /** @noinspection SqlResolve */
        $query = "
            SELECT
                *
            FROM
                information_schema.columns
            WHERE table_name = '$table'
        ";
        
        self::$columnsCache[$table] = [];
        
        $rows = $pdo->query($query);
        while($row = $rows->fetch())
            self::$columnsCache[$table][$row["column_name"]] = $row;
        
        return self::$columnsCache[$table];
    }
    
    
    private static function getColumn(string $table, string $column): array
    {
        return self::getColumns($table)[$column];
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    private static function createProperty(ClassType &$class, array $column): Property
    {
        $name = Casings::snake2camel($column["column_name"]);
        
        $nullable = $column["is_nullable"] === "YES";
        
        $customGetter = null;
        
        switch($column["data_type"])
        {
            case "boolean":
                $type = "bool";
                break;
            case "integer":
                $type = "int";
                break;
            case "character varying":
                $type = "string";
                break;
            case "timestamp without time zone":
                $type = "string";
                $customGetter = (new Method("get".Casings::camel2pascal($name)))
                    ->setVisibility("public")
                    ->addComment("@return \\DateTimeImmutable".($nullable ? "|null" : ""))
                    ->addComment("@throws \\Exception")
                    ->addBody("return new \DateTimeImmutable(\$this->$name);");
                break;
            case "text":
                $type = "string";
                break;
            case "json":
                $type = "string";
                $customGetter = (new Method("get".Casings::camel2pascal($name)))
                    ->setVisibility("public")
                    ->addComment("@return array".($nullable ? "|null" : ""))
                    //->addComment("@throws \\Exception")
                    ->addBody("return json_decode(\$this->$name, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);");
                break;
            case "USER-DEFINED":
                // EXTENSION!
                $extension = $column["udt_name"];
                
                switch($extension)
                {
                    case "citext": // Case-Insensitive Text
                        $type = "string";
                        break;
                    
                    // TODO: Add other extensions as we find them!
                    
                    default:
                        // Unsupported extension, just used string ???
                        $type = "string";
                        break;
                }
                break;
            
            
            default:
                $type = $column["data_type"];
                throw new ModelCreationException("The '$type' data type needs to be added!");
                break;
        }
        
        $_property = (new Property($name))
            ->setVisibility("protected")
            ->addComment("@var $type".($nullable ? "|null" : ""))
            ->addComment($name !== $column["column_name"] ? "@ColumnName ".$column["column_name"] : "");
        
        
        if($class !== null)
        {
            if($customGetter !== null)
            {
                $class->addComment("@see    $type" . ($nullable ? "|null" : "") . " get" . Casings::camel2pascal($name) . "()");
                $class->addMember($customGetter);
            }
            else
            {
                $class->addComment("@method $type" . ($nullable ? "|null" : "") . " get" . Casings::camel2pascal($name) . "()");
            }
            
            
            $class->addMember($_property);
        }
        
        return $_property;
    }
    
    
    
    /**
     * @param string $directory
     * @param string $namespace
     * @param string $table
     * @return string
     * @throws DatabaseConnectionException
     * @throws ModelCreationException
     */
    public static function create(string $directory, string $namespace, string $table): string
    {
        // Ensure the database is connected!
        $pdo = Database::connect();
        
        if($directory !== "" && !file_exists($directory))
            mkdir($directory, 0775, true);
        
        $directory = realpath($directory);
        
        if(!$directory)
            throw new ModelCreationException("The directory '$directory' could not be created!");
        
        if($namespace === "")
            throw new ModelCreationException("The namespace '$namespace' is invalid!");
        
        $className = Casings::snake2pascal($table);
        $class = $namespace."\\".$className;
        
        $_namespace = (new PhpNamespace($namespace))
            ->addUse("rspaeth\\Data\\Models\\Model")
            ->addUse("rspaeth\\Data\\Annotations\\TableNameAnnotation", "TableName")
            ->addUse("rspaeth\\Data\\Annotations\\ColumnNameAnnotation", "ColumnName");
        
        $_class = ($_namespace->addClass($className))
            ->addExtend("rspaeth\\Data\\Models\\Model")
            ->setFinal()
            ->addComment("Class $className")
            ->addComment("")
            ->addComment("@package ".dirname($namespace))
            ->addComment("@author Ryan Spaeth <rspaeth@spaethtech.com>")
            ->addComment("@final")
            ->addComment("")
            ->addComment("@TableName $table");
        
        $columns = self::getColumns($table);
        
        if(count($columns) > 0)
            $_class->addComment("");
        
        foreach($columns as $column)
            $_class->addMember(self::createProperty($_class, $column));
        
        
        
        
        $_code =
            "<?php\n".
            "declare(strict_types=1);\n".
            "\n".
            $_namespace;
        
        file_put_contents($directory."/$className.php", $_code);
        
        
        return $class;
    }
    
    
    
}