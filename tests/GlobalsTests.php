<?php



namespace SpaethTech\UCRM\SDK;

use PHPUnit\Framework\TestCase;

class GlobalsTests extends TestCase
{
    protected const PLUGIN_DIR = __DIR__."/../example/src";
    
    
    
    public function testContainerID()
    {
        $this->assertEquals("", __CONTAINER_ID__);
        
    }
    
    public function testDeployment()
    {
        $this->assertEquals("LOCAL", __DEPLOYMENT__);
    }
    
    public function testUcrmVersion()
    {
        $this->assertEquals("", __UCRM_VERSION__);
    }
    
    public function testProjectDir()
    {
        chdir(self::PLUGIN_DIR);
        var_dump(getcwd());
        $this->assertEquals("", __PROJECT_DIR__);
    }
    
}
