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
    protected $savePath = "/tmp/session";

    /**
     * Session file prefix
     *
     * @var string
     */
    protected $sessionFilePrefix = "sess_";

    /**
     * Set save path
     *
     * @param $savePath
     * @return $this
     */
    public function setSavePath($savePath)
    {
        if (empty($savePath)) return $this;

        $this->savePath = $savePath;

        if (!is_dir($this->savePath)) {
            mkdir($this->savePath);
        }

        return $this;
    }

    /**
     * @param string $savePath
     * @param string $sessionName
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        $this->setSavePath($savePath);

        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        return (string)@file_get_contents($this->savePath . DIRECTORY_SEPARATOR . $this->sessionFilePrefix . $id);
    }

    /**
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data)
    {
        return file_put_contents($this->savePath . DIRECTORY_SEPARATOR . $this->sessionFilePrefix . $id, $data) === false ? false : true;
    }

    /**
     * @param string $id
     * @return bool
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
     * @param int $maxlifetime
     * @return bool
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
     * @param $sessionData
     * @return bool
     */
    protected function validateSession($sessionData)
    {
        return true;
    }
}