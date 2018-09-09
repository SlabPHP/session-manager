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

        $resultMock = $this->getMockBuilder('\mysqli_result')
            ->disableOriginalConstructor()
            ->setMethods(['fetch_object'])
            ->getMock();

        $resultMock
            ->expects($this->once())
            ->method('fetch_object')
            ->willReturn(new TestResponse());

        $statementMock = $this->getMockBuilder('\mysqli_stmt')
            ->disableOriginalConstructor()
            ->setMethods(['bind_param', 'get_result', 'execute', 'close'])
            ->getMock();

        $statementMock
            ->expects($this->once())
            ->method('bind_param')
            ->with('ss', $sessionName, $siteName);

        $statementMock
            ->expects($this->once())
            ->method('get_result')
            ->willReturn($resultMock);

        $statementMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $statementMock
            ->expects($this->once())
            ->method('close')
            ->willReturn(true);

        $mock = $this->getMockBuilder('\Mysqli')
            ->disableOriginalConstructor()
            ->setMethods(['prepare'])
            ->getMock();

        $mock->expects($this->any())
            ->method('prepare')
            ->with('select `agent`, `data` from `main`.`table` where `id` = ? and `site` = ? limit 1')
            ->willReturn($statementMock);

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
    public $id = 'site-name';

    public $data = 'a:2:{s:5:"hello";b:1;s:9:"timestamp";i:1519492959;}';

    public $agent = 'phpunit';
}
