<?php
/**
 * Database session handler
 *
 * @author Eric
 * @package Slab
 * @subpackage Utilities
 */
namespace Slab\Session\Handlers\Database;

class MySQL extends Database
{
    /**
     * @var \Mysqli
     */
    protected $databaseResource;

    /**
     * @param $databaseResource
     * @param $databaseName
     * @param $table
     * @param $siteName
     * @throws \Exception
     * @return $this
     */
    public function setDatabase($databaseResource, $databaseName, $table, $siteName)
    {
        $this->databaseResource = $databaseResource;
        if (!($this->databaseResource instanceof \Mysqli)) {
            throw new \Exception('Please use an object of type \Mysqli as your database resource.');
        }

        $this->database = $databaseName;
        $this->table = $table;
        $this->siteName = $siteName;

        return $this;
    }

    /**
     * @param string $id
     * @return string
     * @throws \Slab\Session\Exceptions\Corruption
     */
    public function read($id)
    {
        try {
            $sql = 'select `agent`, `data` from `' . $this->database . '`.`' . $this->table . '` where `id` = ' . $this->databaseResource->real_escape_string($id) . ' and `site` = ' . $this->databaseResource->real_escape_string($this->siteName) . ' limit 1;';

            $result = $this->databaseResource->query($sql);

        } catch (\Exception $exception) {
            return '';
        }

        if ($result->num_rows == 0) {
            return '';
        }

        $row = $result->fetch_row();

        if (!$this->validateSession($row)) {
            $this->destroy($id);

            $message = "Session corruption on " . $id . " user agent " . $row->agent . " does not match " . $_SERVER["HTTP_USER_AGENT"] . " " . $_SERVER["REMOTE_ADDR"];
            throw new \Slab\Session\Exceptions\Corruption($message);
        }

        return $row->data;
    }

    /**
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data)
    {
        try {
            $escapedData = $this->databaseResource->real_escape_string($data);
            $escapedTime = $this->databaseResource->real_escape_string(date('Y-m-d H:i:s'));

            $sql = 'insert into `' . $this->database . '`.`' . $this->table . '` (`id`, `site`, `ip`, `agent`, `activity`, `data`) values (' ;
            $sql.= $this->databaseResource->real_escape_string($id) . ', ';
            $sql.= $this->databaseResource->real_escape_string($this->siteName) . ', ';
            $sql.= $this->databaseResource->real_escape_string($_SERVER['REMOTE_ADDR']) . ', ';
            $sql.= $this->databaseResource->real_escape_string($_SERVER['HTTP_USER_AGENT']) . ', ';
            $sql.= $escapedTime . ', ';
            $sql.= $escapedData . ') ';
            $sql.= ' on duplicate key update `last_activity` = ' . $escapedTime;
            $sql.= ', `data` = ' . $escapedData. ';';

            $result = $this->databaseResource->query($sql);
        } catch (\Exception $exception) {
            return false;
        }

        return ($result->num_rows ? true : false);
    }

    /**
     * Session Destroy
     *
     * @see Base::destroy()
     */
    public function destroy($id)
    {
        try {
            $sql = 'delete from `' . $this->database . '`.`' . $this->table . '` where `id` = ' . $this->databaseResource->real_escape_string($id) . ' and `site` = ' . $this->databaseResource->real_escape_string($this->siteName) . ' limit 1;';

            $result = $this->databaseResource->query($sql);
        } catch (\Exception $exception) {
            return false;
        }

        return ($result->num_rows ? true : false);
    }

    /**
     * @param $field
     * @param $value
     * @return bool
     */
    public function killByUserDataField($field, $value)
    {
        $input = '%s:' . mb_strlen($field) . ':"' . $field . '"';
        if (is_string($value)) {
            $input .= ';s:' . mb_strlen($value) . ':"' . $value . '"%';
        } elseif (is_integer($value)) {
            $input .= ';i:' . $value . '%';
        } else {
            return false;
        }

        try {
            $sql = 'delete from `' . $this->database . '`.`' . $this->table . '` where `data` like ' . $this->databaseResource->real_escape_string($input) . ' and `site` = ' . $this->databaseResource->real_escape_string($this->siteName) . ' limit 1;';

            $result = $this->databaseResource->real_escape_string($sql);
        } catch (\Exception $exception) {
            return false;
        }

        return ($result->num_rows ? true : false);
    }

    /**
     * Garbage Collection
     *
     * @see Base::gc()
     */
    public function gc($maxlifetime)
    {
        if (!$this->shouldGC()) return false;

        $cutOff = new \DateTime();
        $cutOff->modify('-' . $maxlifetime . ' seconds');

        try {
            $query = 'delete from `' . $this->database . '`.`' . $this->table . '` where activity <= ' . $this->databaseResource->real_escape_string($cutOff->format('Y-m-d H:i:s'));
            $result = $this->databaseResource->query($query);
        } catch (\Exception $exception) {
            return false;
        }

        return ($result->num_rows > 0 ? true : false);
    }
}