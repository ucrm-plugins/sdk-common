<?php
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK\Dynamics;


use SpaethTech\UCRM\SDK\Annotations\AnnotationReader;
use SpaethTech\UCRM\SDK\Collections\Collectible;
use SpaethTech\UCRM\SDK\Support\Strings;

/**
 * Class AutoObject
 *
 * @package MVQN\Common
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 */
class AutoObject extends Collectible
{
    /**
     * @var array An array of cached annotations for this class to be used to speed up look-ups in future calls.
     */
    private static $annotationCache = null;
    
    /**
     * @const array a list of methods for which to ignore when parsing annotations.
     */
    private const IGNORE_METHODS = [
        "__construct",
        "__toString",
        "jsonSerialize"
    ];
    
    /**
     * @const array a list of properties for which to ignore when parsing annotations.
     */
    private const IGNORE_PROPERTIES = [
        "annotationCache"
    ];
    
    
    public function __construct(array $values = [])
    {
        if($values === null || $values === [])
            return;
        
        
        
        foreach($values as $property => $value)
        {
            if(property_exists($this, $property))
            {
                $this->$property = $value;
            }
            else
            {
                $class = get_class($this);
                
                if(!self::$annotationCache || !array_key_exists($class, self::$annotationCache))
                    $this->buildAnnotationCache();
                
                $properties = self::$annotationCache[get_class($this)]["properties"];
                
                foreach($properties as $prop)
                {
                    //if(array_key_exists("Accepts", $prop))
                    //    var_dump($prop["Accepts"]);
                    
                    if(array_key_exists("Accepts", $prop) && in_array($property, $prop["Accepts"]))
                        $this->{$prop["var"]["name"]} = $value;
                }
                
                
                /*
                if(Strings::contains($property, "_"))
                {
                    $camel = Casings::snake2camel($property);

                    if(property_exists($this, $camel))
                    {
                        $this->$camel = $value;
                    }

                }
                */
            }
        }
    }
    
    
    
    
    private function buildAnnotationCache(): void
    {
        $class = get_class($this);
        
        if(self::$annotationCache === null)
            self::$annotationCache = [];
        
        self::$annotationCache[$class] = [];
        
        // Instantiate an Annotation Reader!
        $annotationReader = new AnnotationReader($class);
        self::$annotationCache[$class] = $annotationReader->getAnnotations();
    }
    
    private static function buildStaticAnnotationCache(): void
    {
        $class = get_called_class();
        
        if(self::$annotationCache === null)
            self::$annotationCache = [];
        
        self::$annotationCache[$class] = [];
        
        // Instantiate an Annotation Reader!
        $annotationReader = new AnnotationReader($class);
        self::$annotationCache[$class] = $annotationReader->getAnnotations();
    }
    
    
    
    
    private $_beforeFirstCallOcurred = false;
    private $_afterFirstCallOcurred = false;
    
    public function __call(string $name, array $args)
    {
        $class = get_class($this);
        
        if(!$this->_beforeFirstCallOcurred)
        {
            if(method_exists($class, "__beforeFirstCall"))
                $class->__beforeFirstCall();
            
            $this->_beforeFirstCallOcurred = true;
        }
        
        if(method_exists($class, "__beforeCall"))
            $class->__beforeCall();
        
        // Check to see if a real method already exists for the requested __call()...
        if(method_exists($this, $name))
            return $name($args);
        
        // Build the cache for this class, if it has not already been done!
        if (self::$annotationCache === null || !array_key_exists($class, self::$annotationCache) ||
            self::$annotationCache[$class] === null)
            $this->buildAnnotationCache();
        
        // Handle the cases, where the method called begins with 'get'...
        if(Strings::startsWith($name, "get"))
        {
            $property = lcfirst(str_replace("get", "", $name));
            
            if(!array_key_exists("class", self::$annotationCache[$class]) ||
                !array_key_exists("method", self::$annotationCache[$class]["class"]))
                throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                    $class."'!");
            
            $regex = "/^(?:[\w\|\[\]]*)?\s+(get\w*)\s*\(.*\).*$/";
            $found = false;
            
            foreach (self::$annotationCache[$class]["class"]["method"] as $annotation)
            {
                //if(Strings::startsWith($annotation["name"], "get"))
                if($annotation["name"] === $name)
                    //if(preg_match($regex, $annotation, $matches))
                {
                    //if(in_array($name, $matches))
                    {
                        $found = true;
                        break;
                    }
                }
            }
            
            if(!$found)
                throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                    $class."'!");
            
            // Should be a valid method by this point!
            
            if(!property_exists($this, $property))
                throw new \Exception("Property '$property' was not found in class '$class', so method '$name' could ".
                    "not be called!");
            
            if(!$this->_afterFirstCallOcurred)
            {
                if (method_exists($class, "__afterFirstCall"))
                    $return = $class->__afterFirstCall($return);
                
                $this->_afterFirstCallOcurred = true;
            }
            
            if(method_exists($class, "__afterCall"))
                $return = $class->__afterCall($return);
            
            return $this->{$property};
        }
        else if(Strings::startsWith($name, "set"))
        {
            $property = lcfirst(str_replace("set", "", $name));
            
            if(!array_key_exists("class", self::$annotationCache[$class]) ||
                !array_key_exists("method", self::$annotationCache[$class]["class"]))
                throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                    $class."'!");
            
            //$regex = "/^(?:[\w\|\[\]]*)?\s+(set\w*)\s*\(.*\).*$/";
            $found = false;
            
            foreach (self::$annotationCache[$class]["class"]["method"] as $annotation)
            {
                //if(Strings::startsWith($annotation["name"], "set"))
                if($annotation["name"] === $name)
                    //if(preg_match($regex, $annotation, $matches))
                {
                    //if(in_array($name, $matches))
                    {
                        $found = true;
                        break;
                    }
                }
            }
            
            if(!$found)
                throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                    $class."'!");
            
            // Should be a valid method by this point!
            
            if(!property_exists($this, $property))
                throw new \Exception("Property '$property' was not found in class '$class', so method '$name' could ".
                    "not be called!");
            
            $value = $args[0];
            
            if (!$this->_afterFirstCallOcurred)
            {
                if (method_exists($class, "__afterFirstCall"))
                    $value = $class->__afterFirstCall($value);
                
                $this->_afterFirstCallOcurred = true;
            }
            
            if(method_exists($class, "__afterCall"))
                $value = $class->__afterCall($value);
            
            $this->{$property} = $value;
            return $this;
        }
        else
        {
            throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                $class."'!");
        }
        
        
    }
    
    
    
    private static $_beforeFirstStaticCallOccurred = [];
    private static $_afterFirstStaticCallOccurred = [];
    
    public static function __callStatic(string $name, array $args)
    {
        $class = get_called_class();
        
        if(!array_key_exists($class, self::$_beforeFirstStaticCallOccurred) ||
            !self::$_beforeFirstStaticCallOccurred[$class])
        {
            if(method_exists($class, "__beforeFirstStaticCall"))
                $class::__beforeFirstStaticCall();
            
            self::$_beforeFirstStaticCallOccurred[$class] = true;
        }
        
        if(method_exists($class, "__beforeStaticCall"))
            $class::__beforeStaticCall();
        
        // Check to see if a real method already exists for the requested __call()...
        if(method_exists($class, $name))
            return $name($args);
        
        $object = new $class();
        
        // Build the cache for this class, if it has not already been done!
        if (self::$annotationCache === null || !array_key_exists($class, self::$annotationCache) ||
            self::$annotationCache[$class] === null)
            self::buildStaticAnnotationCache();
        
        /*
        if(!array_key_exists($class, self::$annotationCache) || self::$annotationCache[$class] === null)
        {
            self::buildStaticAnnotationCache();
        }
        */
        
        // Handle the cases, where the method called begins with 'get'...
        if(Strings::startsWith($name, "get"))
        {
            $property = lcfirst(str_replace("get", "", $name));
            
            if (!array_key_exists("class", self::$annotationCache[$class]) ||
                !array_key_exists("method", self::$annotationCache[$class]["class"]))
                throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                    $class."'!");
            
            $regex = "/^(?:[\w\|\[\]]*)?\s+(get\w*)\s*\(.*\).*$/";
            $found = false;
            
            foreach (self::$annotationCache[$class]["class"]["method"] as $annotation)
            {
                $methodName = $annotation["name"];
                
                //if(Strings::startsWith($annotation["name"], "get"))
                if($annotation["name"] === $name)
                    //if(preg_match($regex, $annotation, $matches))
                {
                    //if(in_array($name, $matches))
                    {
                        $found = true;
                        break;
                    }
                }
            }
            
            if(!$found)
                throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                    $class."'!");
            
            // Should be a valid method by this point!
            
            if(!property_exists($class, $property))
                throw new \Exception("Property '$property' was not found in class '$class', so method '$name' could ".
                    "not be called!");
            
            $return = $class::$$property;
            
            if(!self::$_afterFirstStaticCallOccurred)
            {
                if (method_exists($class, "__afterFirstStaticCall"))
                    $return = $class::__afterFirstStaticCall($return);
                
                self::$_afterFirstStaticCallOccurred = true;
            }
            
            if(method_exists($class, "__afterStaticCall"))
                $return = $class::__afterStaticCall($return);
            
            return $return;
        }
        elseif(Strings::startsWith($name, "set"))
        {
            $property = lcfirst(str_replace("set", "", $name));
            
            if(!array_key_exists("class", self::$annotationCache[$class]) ||
                !array_key_exists("method", self::$annotationCache[$class]["class"]))
                throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                    $class."'!");
            
            //$regex = "/^(?:[\w\|\[\]]*)?\s+(set\w*)\s*\(.*\).*$/";
            $found = false;
            
            foreach (self::$annotationCache[$class]["class"]["method"] as $annotation)
            {
                //if(Strings::startsWith($annotation["name"], "set"))
                if($annotation["name"] === $name)
                    //if(preg_match($regex, $annotation, $matches))
                {
                    //if(in_array($name, $matches))
                    {
                        $found = true;
                        break;
                    }
                }
            }
            
            if(!$found)
                throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                    $class."'!");
            
            // Should be a valid method by this point!
            
            if(!property_exists($class, $property))
                throw new \Exception("Property '$property' was not found in class '$class', so method '$name' could ".
                    "not be called!");
            
            $value = $args[0];
            
            if (!array_key_exists($class, self::$_afterFirstStaticCallOccurred) ||
                !self::$_afterFirstStaticCallOccurred[$class])
            {
                if (method_exists($class, "__afterFirstStaticCall"))
                    $value = $class::__afterFirstStaticCall($value);
                
                self::$_afterFirstStaticCallOccurred[$class] = true;
            }
            
            if(method_exists($class, "__afterStaticCall"))
                $value = $class::__afterStaticCall($value);
            
            $class::$$property = $value;
        }
        else
        {
            throw new \Exception("Method '$name' was either not defined or does not have an annotation in class '".
                $class."'!");
        }
        
        //return null;
    }
    
    
    
}