<?php
/**
 * Handler Mock
 *
 * @package Slab
 * @subpackage Tests
 * @author Eric
 */
namespace Slab\Tests\Session\Mocks;

class Handler implements \SessionHandlerInterface
{
    /**
     * @var array
     */
    private $seedData = [];

    /**
     * @var bool
     */
    private $writeSteps = false;

    /**
     * @param $seedData
     * @return $this
     */
    public function seedData($seedData)
    {
        $this->seedData = $seedData;

        return $this;
    }

    /**
     * @param $writeSteps
     * @return $this
     */
    public function setWriteSteps($writeSteps)
    {
        $this->writeSteps = $writeSteps;

        return $this;
    }

    /**
     * Open a session
     *
     * @param string $parameter
     * @param string $token
     * @return bool
     */
    public function open($parameter, $token)
    {
        if ($this->writeSteps) echo 'session.open' . PHP_EOL;
        return true;
    }

    /**
     * Close a session
     */
    public function close()
    {
        if ($this->writeSteps) echo 'session.close' . PHP_EOL;
    }

    /**
     * Read from a session
     *
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        if ($this->writeSteps) echo 'session.read' . PHP_EOL;
        return serialize($this->seedData);
    }

    /**
     * Write to a session
     *
     * @param string $id
     * @param string $data
     */
    public function write($id, $data)
    {
        if ($this->writeSteps) echo 'session.write' . PHP_EOL;
        $this->seedData = unserialize($data);
    }

    /**
     * Destroy a session
     *
     * @param string $id
     * @return bool
     */
    public function destroy($id)
    {
        if ($this->writeSteps) echo 'session.destroy-' . $id . PHP_EOL;
    }

    /**
     * Perform garbage collection
     *
     * @param integer $maxLifeTime
     */
    public function gc($maxLifeTime)
    {
        if ($this->writeSteps) echo 'session.gc' . PHP_EOL;
    }
}