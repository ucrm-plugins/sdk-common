<?php /** @noinspection PhpUnused */
declare( strict_types=1 );

namespace SpaethTech\UCRM\SDK\Composer\Plugins\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Command\BaseCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use SpaethTech\UCRM\SDK\Composer\Plugins\Commands\Helpers\Project;

/**
 * Class BundleCommand
 *
 * @package   SpaethTech\UCRM\SDK
 *
 * @author    Ryan Spaeth (rspaeth@spaethtech.com)
 * @copyright 2022 Spaeth Technologies Inc.
 *
 */
class BundleCommand extends BaseCommand
{
    /**
     * Configures this command for use with the composer system.
     */
    protected function configure()
    {
        $this->setName( "bundle" );
        $this->setDescription("Bundles this project into a UCRM Plugin package.");
        
        $this->addOption( "no-dev", null, InputOption::VALUE_NONE,     "Bundle without development dependencies." );
        $this->addOption( "file",   null, InputOption::VALUE_REQUIRED, "Bundle using file name." );
        $this->addOption( "suffix", null, InputOption::VALUE_REQUIRED, "Bundle using file suffix." );
        $this->addOption( "dir",    null, InputOption::VALUE_REQUIRED, "Bundle file location." );
        
    }
    
    /**
     * Executes when this command is used.
     *
     * @param InputInterface  $input    Input from the composer system.
     * @param OutputInterface $output   Output to the composer system.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Perform project validation.
        $valid = Project::validate($input, $output);
        
        if(!$valid)
            return 0;
        
        
        
        chdir(__PROJECT_DIR__);
        
        $manifest = json_decode( file_get_contents( "src/manifest.json" ), true );
        
        
        
        $fs = new Filesystem();
        
        $fs->remove("src/composer.json");
        $fs->remove("src/composer.lock");
        
        $fs->copy("composer.json", "src/composer.json");
        $fs->copy("composer.lock", "src/composer.lock");
        
        Project::fixSubFolders();
        
        //exit;
        
        $io = new SymfonyStyle($input, $output);
        $io->newLine();
        
        #region OPTIONS
        
        $headers = [ "Option", "Value", "Note" ];
        $rows    = [];
        
        $format = $this->getComposer()->getConfig()->get("archive-format");
        
        if( !$format || $format !== "zip" )
        {
            $rows[] = [ "archive-format", "zip", "Forced, per the UCRM Plugin requirements." ];
            $format = "zip";
        }
        
        $noDev = !( $input->getOption( "no-dev" ) === FALSE )
            || ( $this->getComposer()->getPackage()->getExtra()["bundle"]["no-dev"] ?? FALSE );
        
        $rows[] = [ "no-dev", ( $noDev ? "true" : "false" ),
            "Bundled ". ( $noDev ? "without" : "with" ) . " development dependencies." ];
        
        $file = $input->getOption("file")
            ?? $this->getComposer()->getPackage()->getExtra()["bundle"]["file"]
            ?? __PLUGIN_NAME__;
        
        $rows[] = [ "file", $file, "" ];
        
        $suffix = $input->getOption("suffix")
            ?? $this->getComposer()->getPackage()->getExtra()["bundle"]["suffix"]
            ?? "";
        
        if( preg_match('#([A-Za-z0-9._-]*)({[A-Z_]+})?([A-Za-z0-9._-]*)#m', $suffix, $matches) !== false )
        {
            $suffix = $matches[1];
            
            switch( $matches[2] )
            {
                case "":
                    break;
                
                case "{PLUGIN_VERSION}":
                    $suffix .= $manifest["information"]["version"];
                    break;
                
                // TODO: Add other suffix variables, as needed!
                
                default;
                    $io->error( [
                        "An unsupported variable '$matches[2]' was supplied in the 'suffix' option!",
                        "Currently supported variables are: PLUGIN_VERSION"
                    ] );
                    exit;
            }
            
            $suffix .= $matches[3];
        }
        
        $rows[] = [ "suffix", $suffix, "" ];
        
        $dir = $input->getOption("dir")
            ?? $this->getComposer()->getPackage()->getExtra()["bundle"]["dir"]
            ?? __PROJECT_DIR__; // . "/src/";
        
        $abs = Project::isAbsolutePath($dir) ? $dir : getcwd() . "/$dir";
        
        if( !realpath($abs) )
            mkdir( $dir, 0777, TRUE );
        
        $path = realpath( $abs );
        $name = $file . ($suffix ? "-$suffix" : "");
        
        $rows[] = [ "dir", $path, "" ];
        $io->table($headers, $rows);
        
        #endregion
        
        if( $noDev )
        {
            /*
            if( !file_exists( __PROJECT_DIR__ . "/tmp/" ) )
                mkdir( __PROJECT_DIR__ . "/tmp/", 0777, true );

            $io->block( "Creating 'vendor' backup...", null, "fg=green", "" );
            $fs->remove( "tmp/vendor_bak" );
            $fs->mirror( "src/vendor", "tmp/vendor_bak" );
            */
            
            $io->block( "Updating production dependencies...", null, "fg=green", "" );
            echo exec( "cd src && composer update --no-interaction --no-dev --ansi" );
        }
        else
        {
            $io->block( "Updating development dependencies...", null, "fg=green", "" );
            echo exec( "cd src && composer update --no-interaction --ansi" );
        }
        
        $io->newLine();
        echo exec( "cd src && composer archive --file=\"$name\" --dir=\"$path\" --format=$format --ansi" );
        $io->newLine(2);
        
        if( $noDev )
        {
            /*
            $io->block( "Restoring 'vendor' backup...", null, "fg=green", "" );
            $fs->remove( "src/vendor" );
            $fs->rename( "tmp/vendor_bak", "src/vendor" );
            */
        }
        
        //$io->block( "Restoring autoload class-maps...", null, "fg=green", "" );
        //echo exec( "composer dump-autoload --no-interaction --ansi" );
        $io->block( "Updating development dependencies...", null, "fg=green", "" );
        echo exec( "cd src && composer update --no-interaction --ansi" );
        $io->newLine(2);
        
        $io->block( "Cleaning up...", null, "fg=green", "" );
        
        $fs->remove("src/composer.json");
        $fs->remove("src/composer.lock");
        
        $io->success("Plugin bundle created successfully at: '$path".DIRECTORY_SEPARATOR."$name.$format'");
        
        return 0;
    }
    
    
    
    
    
    
    
    
    
    
}
