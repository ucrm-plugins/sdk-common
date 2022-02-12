<?php
declare( strict_types=1 );

namespace SpaethTech\UCRM\SDK\Composer\Plugins\Commands\Helpers;

use Deployment;
use JsonSchema\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class Project
 *
 * @package   SpaethTech\UCRM\SDK
 *
 * @author    Ryan Spaeth (rspaeth@spaethtech.com)
 * @copyright 2022 Spaeth Technologies Inc.
 *
 */
class Project
{
    /**
     * Handles validation of the project prior to the plugin's execution.
     *
     * @param InputInterface  $input    Input from the composer system.
     * @param OutputInterface $output   Output to the composer system.
     *
     * @return bool                     Returns TRUE if the project appears to be valid, otherwise FALSE.
     */
    public static function validate(InputInterface $input, OutputInterface $output): bool
    {
        $io = new SymfonyStyle($input, $output);
        
        if( __DEPLOYMENT__ === Deployment::REMOTE )
        {
            $io->error( [
                "The 'bundle' command cannot be used on a remotely deployed project."
            ] );
            return false;
        }
        
        if( !file_exists( __PROJECT_DIR__ . "/src" ) || !is_dir( __PROJECT_DIR__ . "/src" ) )
        {
            $io->error( [
                "The Plugin's code is expected to reside at: '" . __PROJECT_DIR__ . DIRECTORY_SEPARATOR . "src'.",
                "See: https://gitlab.com/ucrm-plugins/skeleton"
            ] );
            return false;
        }
        
        if( !file_exists( __PLUGIN_DIR__ . "/manifest.json" ) || !file_exists( __PLUGIN_DIR__ . "/main.php" ) )
        {
            $io->error( [
                "The Plugin at: '".__PLUGIN_DIR__."' does not contain the required files.",
                "See: https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/docs/file-structure.md#required-files",
            ] );
            return false;
        }
        
        $manifest = json_decode( file_get_contents( __PLUGIN_DIR__ . "/manifest.json" ), true );
        
        if( ( $error = json_last_error() ) !== JSON_ERROR_NONE )
        {
            $io->error( [
                "An error occurred while parsing the Plugin's 'manifest.json' file.",
                "Error: $error"
            ] );
            return false;
        }
        
        $validator = new Validator();
        $validator->validate( $manifest, (object)[
            '$ref' => (object)json_decode( file_get_contents( __DIR__ . "/../../../../../manifest.schema.json" ), true )
        ] );
        
        if ( !$validator->isValid() )
        {
            $errors = [
                "The Plugin's 'manifest.json' file does not validate against the required schema."
            ];
            
            foreach( $validator->getErrors() as $error )
                $errors[] = sprintf( "[%s] %s\n", $error["property"], $error["message"] );
            
            $io->error( $errors );
            return false;
        }
        
        // TODO: Add more validation, as needed!
        
        return true;
    }
    
    /**
     * Determines whether a path is absolute or relative.
     *
     * @param string $path              The path to examine.
     *
     * @return bool                     Returns TRUE if the path is absolute, otherwise FALSE.
     */
    public static function isAbsolutePath( string $path ): bool
    {
        return ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) || strpos( $path, "/" ) === 0 );
        
    }
    
    /**
     * Refactors relative paths inside the specified file.
     *
     * @param string $path              An optional file on which to operate, defaults to the Project's 'composer.json'.
     */
    public static function fixSubFolders( string $path = __PROJECT_DIR__ . "/src/composer.json" )
    {
        $folders = [];
        
        foreach( scandir( __PROJECT_DIR__ ) as $file )
            if( $file !== "." && $file !== ".." && is_dir($file) && $file !== "src" )
                $folders[] = $file;
        
        $contents = file_get_contents( $path );
        
        $contents = preg_replace( '#("(?:./)?src/?)#m', '"', $contents );
        $contents = preg_replace( '#"../#m', '"../../', $contents );
        
        foreach( $folders as $folder )
        {
            if($folder === "dev")
                continue;
            
            $contents = preg_replace('#("(?:./)?' . $folder . '/?)#m', '"../' . $folder . '/', $contents);
        }
        
        //$contents = preg_replace( '#"../../sdk#m', '"../../../sdk', $contents );
        
        
        file_put_contents( $path, $contents );
        
    }
    
}
