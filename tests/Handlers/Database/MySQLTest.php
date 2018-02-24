<?php
/**
 * MySQL Test
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Handlers\Database;

class MySQLTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Very basic test of handler
     *
     * @throws \Exception
     * @throws \Slab\Session\Exceptions\Corruption
     */
    public function testHandler()
    {
        $sessionName = 'test-session';
        $siteName = 'site-name';

        $mock = $this->getMockBuilder('\Mysqli')
            ->setMethods(['real_escape_string', 'query'])
            ->getMock();

        $mock->expects($this->any())
            ->method('real_escape_string')
            ->willReturn('ESCAPED');

        $mock->expects($this->once())
            ->method('query')
            ->with($this->equalTo("select `agent`, `data` from `main`.`table` where `id` = ESCAPED and `site` = ESCAPED limit 1;"))
            ->willReturn(new TestResponse());

        $handler = new \Slab\Session\Handlers\Database\MySQL();
        $handler->setDatabase($mock, 'main', 'table', $siteName);

        $this->assertEquals(true, $handler->open('', $sessionName));

        $data = unserialize($handler->read($sessionName));

        $this->assertEquals(true, $data['hello']);
        $this->assertEquals(1519492959, $data['timestamp']);
    }
}

class TestResponse
{
    public $num_rows = 1;

    public function fetch_row()
    {
        $object = new \stdClass();

        $object->id = 'site-name';
        $object->data = 'a:2:{s:5:"hello";b:1;s:9:"timestamp";i:1519492959;}';
        $object->agent = 'phpunit';

        return $object;
    }
}
