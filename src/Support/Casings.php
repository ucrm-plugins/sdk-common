<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK\Support;

/**
 * A collection of helper functions to convert between some common string casings.
 *
 * @package MVQN\Common
 */
final class Casings
{
    /**
     * Converts a PascalCase string to it's snake_case equivalent.
     *
     * @param string $pascal The PascalCase string to convert.
     * @return string Return the snake_case equivalent.
     */
    public static function pascal2snake(string $pascal): string
    {
        preg_match_all('/((?:^|[A-Z])[a-z]+)/', $pascal, $matches);
        
        if($matches !== null && count($matches) > 1 && count($matches[1]) > 1)
        {
            $nameParts = $matches[1];
            /** @noinspection SpellCheckingInspection */
            $nameParts = array_map("lcfirst", $nameParts);
            return implode("_", $nameParts);
        }
        else
        {
            return lcfirst($pascal);
        }
    }
    
    /**
     * Converts a PascalCase string to it's camelCase equivalent.
     *
     * @param string $pascal The PascalCase string to convert.
     * @return string Return the camelCase equivalent.
     */
    public static function pascal2camel(string $pascal): string
    {
        return lcfirst($pascal);
    }
    
    /**
     * @param string $pascal
     *
     * @return string
     */
    public static function pascal2lisp(string $pascal): string
    {
        return str_replace("_", "-", self::pascal2snake($pascal));
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * @param string $lisp
     *
     * @return string
     */
    public static function lisp2camel(string $lisp): string
    {
        return lcfirst(self::snake2pascal($lisp));
    }
    
    /**
     * @param string $lisp
     *
     * @return string
     */
    public static function lisp2pascal(string $lisp): string
    {
        $nameParts = explode("-", $lisp);
        /** @noinspection SpellCheckingInspection */
        $nameParts = array_map("ucfirst", $nameParts);
        return implode("", $nameParts);
    }
    
    /**
     * @param string $lisp
     *
     * @return string
     */
    public static function lisp2snake(string $lisp): string
    {
        $nameParts = explode("-", $lisp);
        return implode("_", $nameParts);
    }
    
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Converts a snake_case string to it's PascalCase equivalent.
     *
     * @param string $snake The snake_case string to convert.
     * @return string Return the PascalCase equivalent.
     */
    public static function snake2pascal(string $snake): string
    {
        $nameParts = explode("_", $snake);
        /** @noinspection SpellCheckingInspection */
        $nameParts = array_map("ucfirst", $nameParts);
        return implode("", $nameParts);
    }
    
    /**
     * Converts a snake_case string to it's camelCase equivalent.
     *
     * @param string $snake The snake_case string to convert.
     * @return string Return the camelCase equivalent.
     */
    public static function snake2camel(string $snake): string
    {
        return lcfirst(self::snake2pascal($snake));
    }
    
    public static function snake2lisp(string $snake): string
    {
        return str_replace("_", "-", $snake);
    }
    
    // -----------------------------------------------------------------------------------------------------------------
    
    /**
     * Converts a camelCase string to it's PascalCase equivalent.
     *
     * @param string $camel The camelCase string to convert.
     * @return string Return the PascalCase equivalent.
     */
    public static function camel2pascal(string $camel): string
    {
        return ucfirst($camel);
    }
    
    /**
     * Converts a camelCase string to it's snake_case equivalent.
     *
     * @param string $camel The camelCase string to convert.
     * @return string Return the snake_case equivalent.
     */
    public static function camel2snake(string $camel): string
    {
        return self::pascal2snake(ucfirst($camel));
    }
    
    /**
     * @param string $camel
     *
     * @return string
     */
    public static function camel2lisp(string $camel): string
    {
        return str_replace("_", "-", self::camel2snake($camel));
    }
    
    
    
    
    
    
}

