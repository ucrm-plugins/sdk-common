<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace SpaethTech\UCRM\SDK\Support;

final class Strings
{
    
    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        $pos = strrpos($subject, $search);
        
        if($pos !== false)
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        
        return $subject;
    }
    
    
    
    /**
     * @param string $haystack The 'haystack' for which to check occurrences of the 'needle'.
     * @param string $needle The 'needle' for which to search the 'haystack'.
     * @return bool Returns TRUE if the 'haystack' contains one or more occurrences of the 'needle', otherwise FALSE.
     */
    public static function contains(?string $haystack, string $needle): bool
    {
        return $haystack !== null && (strpos($haystack, $needle) !== false);
    }
    
    /**
     * @param string $haystack The 'haystack' for which to examine the first character.
     * @return bool Returns TRUE if the 'haystack' starts with an uppercase letter, otherwise FALSE.
     */
    public static function startsWithUpper(?string $haystack): bool
    {
        return $haystack !== null && (preg_match('/[A-Z]$/',$haystack{0}) == true);
    }
    
    /**
     * @param string $haystack The 'haystack' for which to examine the beginning character(s).
     * @param string $needle The 'needle' for which to search the 'haystack'.
     * @return bool Returns TRUE if the 'haystack' begins with the 'needle', otherwise FALSE.
     */
    public static function startsWith(?string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        return $haystack !== null && (substr($haystack, 0, $length) === $needle);
    }
    
    /**
     * @param string $haystack The 'haystack' for which to examine the ending character(s).
     * @param string $needle The 'needle' for which to search the 'haystack'.
     * @return bool Returns TRUE if the 'haystack' ends with the 'needle', otherwise FALSE.
     */
    public static function endsWith(?string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        return $haystack !== null && ($length == 0 ? true : (substr($haystack, -$length) === $needle));
    }
    
}

