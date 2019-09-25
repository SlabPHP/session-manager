<?php
/**
 * Tests for File Handler
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Session\Handlers;

class FileTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test a simple read and write
     */
    public function testHandler()
    {
        $sessionName = 'test-session';
        $sessionDir = __DIR__ . '/../data';

        $handler = new \Slab\Session\Handlers\File();
        $this->assertEquals(true, $handler->open($sessionDir, $sessionName));
        $data = unserialize($handler->read($sessionName));

        $this->assertEquals(null, $data['hello']);

        $time = time();
        $handler->write($sessionName, serialize(['hello'=>true,'timestamp'=>$time]));
        $handler->close();

        unset($handler);

        $handler = new \Slab\Session\Handlers\File();
        $this->assertEquals(true, $handler->open($sessionDir, $sessionName));
        $data = unserialize($handler->read($sessionName));

        $this->assertEquals(true, $data['hello']);
        $this->assertEquals($time, $data['timestamp']);
    }
}