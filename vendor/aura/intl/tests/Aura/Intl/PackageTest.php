<?php
namespace Aura\Intl;

/**
 * Test class for Package.
 * Generated by PHPUnit on 2012-10-27 at 22:46:01.
 */
class PackageTest extends \PHPUnit_Framework_TestCase
{
    protected $package;
    
    protected function setUp()
    {
        parent::setUp();
        $factory = new PackageFactory;
        $this->package = $factory->newInstance([
            'fallback' => 'Vendor.Fallback',
            'formatter' => 'intl',
            'messages' => [
                'ERR_NO_SUCH_OPTION' => "The option {option} is not recognized.",
            ],
        ]);
    }

    public function testGet()
    {
        $expect = 'Vendor.Fallback';
        $actual = $this->package->getFallback();
        $this->assertSame($expect, $actual);
        
        $expect = 'intl';
        $actual = $this->package->getFormatter();
        $this->assertSame($expect, $actual);
        
        $expect = [
            'ERR_NO_SUCH_OPTION' => "The option {option} is not recognized.",
        ];
        $actual = $this->package->getMessages();
        $this->assertSame($expect, $actual);
        
    }
}
