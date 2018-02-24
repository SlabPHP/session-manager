<?php
/**
 * File based session handler
 *
 * @author Eric
 * @package Slab
 * @subpackage Utilities
 */
namespace Slab\Session\Handlers;

class File extends Base
{
    /**
     * Save path
     *
     * @var string
     */
    protected $savePath = "cache";

    /**
     * Session file prefix
     *
     * @var string
     */
    protected $sessionFilePrefix = "sess_";

    /**
     * Session Open
     *
     * @see SessionHandler::open()
     */
    public function open($savePath, $sessionName)
    {
        $this->savePath = $savePath;

        if (!is_dir($this->savePath)) {
            mkdir($this->savePath);
        }

        return true;
    }

    /**
     * Session Close
     *
     * @see SessionHandler::close()
     */
    public function close()
    {
        return true;
    }

    /**
     * Session Read
     *
     * @see SessionHandler::read()
     */
    public function read($id)
    {
        return (string)@file_get_contents($this->savePath . DIRECTORY_SEPARATOR . $this->sessionFilePrefix . $id);
    }

    /**
     * Session Write
     *
     * @see SessionHandler::write()
     */
    public function write($id, $data)
    {
        return file_put_contents($this->savePath . DIRECTORY_SEPARATOR . $this->sessionFilePrefix . $id, $data) === false ? false : true;
    }

    /**
     * Session Destroy
     *
     * @see SessionHandler::destroy()
     */
    public function destroy($id)
    {
        $file = $this->savePath . DIRECTORY_SEPARATOR . $this->sessionFilePrefix . $id;

        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    /**
     * Garbage Collection
     *
     * @see SessionHandler::gc()
     */
    public function gc($maxlifetime)
    {
        foreach (glob($this->savePath . DIRECTORY_SEPARATOR . $this->sessionFilePrefix . '*') as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * @see \Slab\Session\Handlers\Base::validateSession()
     */
    protected function validateSession($sessionData)
    {
        return true;
    }
}