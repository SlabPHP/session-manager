<?php
/**
 * Database session handler
 *
 * @author Eric
 * @package Slab
 * @subpackage Utilities
 */
namespace Slab\Session\Handlers\Database;

abstract class Database extends \Slab\Session\Handlers\Base
{
    /**
     * @var resource
     */
    protected $databaseResource;

    /**
     * Database Name
     *
     * @var string
     */
    protected $database;

    /**
     * Table Name
     *
     * @var string
     */
    protected $table;

    /**
     * Site Name
     *
     * @var string
     */
    protected $siteName;

    /**
     * @var bool
     */
    private $validateUserAgent = false;

    /**
     * @param $validateUserAgent
     * @return $this
     */
    public function setValidateUserAgent($validateUserAgent)
    {
        $this->validateUserAgent = $validateUserAgent;

        return $this;
    }

    /**
     * @param $databaseResource
     * @param $databaseName
     * @param $table
     * @param $siteName
     * @return mixed
     */
    abstract public function setDatabase($databaseResource, $databaseName, $table, $siteName);

    /**
     * Validate returned session data
     */
    protected function validateSession($sessionData)
    {
        if ($this->validateUserAgent)
        {
            return ($sessionData->agent == $_SERVER['HTTP_USER_AGENT']);
        }

        return true;
    }

    /**
     * Session Open
     *
     * @see Base::open()
     */
    public function open($savePath, $sessionName)
    {
        if (empty($this->table) || empty($this->database) || empty($this->siteName)) {
            return false;
        }

        return true;
    }

    /**
     * Session Close
     *
     * @see Base::close()
     */
    public function close()
    {
        return true;
    }
}