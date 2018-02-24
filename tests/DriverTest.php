<?php
/**
 * Session Driver Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Session;

class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test session driver
     */
    public function testSessionDriver()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        $handler = new Mocks\Handler();

        $handler
            ->seedData(['variable 1'=>true, 'variable 2'=>false])
            ->setWriteSteps(true);

        $driver = new \Slab\Session\Driver();

        $driver
            ->setHandler($handler)
            ->start();

        $driver->set('test', true);
        $this->assertEquals(true, $driver->get('test'));

        $this->assertEquals(true, $driver->get('variable 1'));
        $this->assertEquals(false, $driver->get('variable 2'));

        $driver->delete('test');
        $this->assertEmpty($driver->get('test'));

        $this->expectOutputString('session.open' . PHP_EOL . 'session.read' . PHP_EOL . 'session.write' . PHP_EOL . 'session.gc' . PHP_EOL . 'session.close' . PHP_EOL);
        unset($driver);
    }

    /**
     * @throws \Slab\Session\Exceptions\Irreparable
     */
    public function testFlashData()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        $handler = new Mocks\Handler();
        $handler->seedData(['@flash'=>true,'regular'=>true]);

        $driver = new \Slab\Session\Driver();

        $driver
            ->setHandler($handler)
            ->start();

        $this->assertEquals(true, $driver->get('@flash'));
        $this->assertEquals(true, $driver->get('regular'));

        $driver->commit();

        unset($driver);

        $driver = new \Slab\Session\Driver();

        $driver
            ->setHandler($handler)
            ->start();

        $this->assertEmpty($driver->get('@flash'));
        $this->assertEquals(true, $driver->get('regular'));
    }
}