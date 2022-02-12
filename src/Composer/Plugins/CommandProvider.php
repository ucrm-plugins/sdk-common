<?php /** @noinspection PhpUnused */
declare( strict_types=1 );

namespace SpaethTech\UCRM\SDK\Composer\Plugins;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Composer\Plugin\PluginInterface as Plugin;
use Composer\Command\BaseCommand as Command;

/**
 * Class CommandProvider
 *
 * @package   SpaethTech\UCRM\SDK
 *
 * @author    Ryan Spaeth (rspaeth@spaethtech.com)
 * @copyright 2022 Spaeth Technologies Inc.
 *
 */
class CommandProvider implements CommandProviderCapability
{
    /**
     * Get the {@see Command}s this {@see Plugin} provides.
     *
     * @return Command[]
     */
    public function getCommands(): array
    {
        return [
            new Commands\BundleCommand,
            new Commands\HookCommand,
        ];
        
    }
    
}
