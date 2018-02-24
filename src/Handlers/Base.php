<?php
/**
 * Base session handler
 *
 * @author Eric
 * @package Slab
 * @subpackage Utilities
 */
namespace Slab\Session\Handlers;

abstract class Base implements \SessionHandlerInterface
{
    /**
     * @var float
     */
    private $gcChance = 1.0;

    /**
     * @param $chance
     * @return $this
     */
    public function setGCChance($chance)
    {
        $this->gcChance = $chance;

        return $this;
    }

    /**
     * @return bool
     */
    protected function shouldGC()
    {
        if ($this->gcChance >= 1.0) {
            return true;
        }

        return ((mt_rand(1,100) / 100.0) > $this->gcChance);
    }

    /**
     * Open a session
     *
     * @param string $parameter
     * @param string $token
     */
    abstract public function open($parameter, $token);

    /**
     * Close a session
     */
    abstract public function close();

    /**
     * Read from a session
     *
     * @param string $id
     */
    abstract public function read($id);

    /**
     * Write to a session
     *
     * @param string $id
     * @param string $data
     */
    abstract public function write($id, $data);

    /**
     * Destroy a session
     *
     * @param string $id
     */
    abstract public function destroy($id);

    /**
     * Perform garbage collection
     *
     * @param integer $maxLifeTime
     */
    abstract public function gc($maxLifeTime);

    /**
     * Validate returned session data
     *
     * You should check user agent but not IP address.
     *
     * @return boolean
     */
    abstract protected function validateSession($sessionData);

    /**
     * Use native session handling instead of the driver
     */
    public function beginNativeSessionUsage()
    {
        session_set_save_handler($this, true);
        session_start();
    }
}

