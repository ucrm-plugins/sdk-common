<?php

namespace SpaethTech\UCRM\SDK;

use PHPUnit\Framework\TestCase;

class PluginTests extends TestCase
{
    protected const PLUGIN_DIR = __DIR__."/../example/src";
    
    public function testEnvironment()
    {
        var_dump(Plugin::mode());
    }
    
    public function testInitialize()
    {
        Plugin::initialize(self::PLUGIN_DIR);
    }
    
    
}
