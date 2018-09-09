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
            $sessionSQL = sprintf(
                'select `agent`, `data` from `%s`.`%s` where `id` = ? and `site` = ? limit 1',
                $this->database,
                $this->table
            );

            $statement = $this->databaseResource->prepare($sessionSQL);

            $statement->bind_param('ss', $id, $this->siteName);

            $statement->execute();

            $result = $statement->get_result();

            if (empty($result)) {
                throw new \Exception("No result returned.");
            }

            $sessionData = $result->fetch_object();

            $statement->close();

            if (empty($sessionData->data)) {
                throw new \Exception("No data returned.");
            }

        } catch (\Exception $exception) {
            die($exception->getMessage());
            return '';
        }

        if (!$this->validateSession($sessionData)) {
            $this->destroy($id);

            $message = sprintf(
                "Session corruption on %s user agent %s does not match %s %s.",
                $id,
                $sessionData->agent,
                $_SERVER["HTTP_USER_AGENT"],
                $_SERVER["REMOTE_ADDR"]
            );

            throw new \Slab\Session\Exceptions\Corruption($message);
        }

        return $sessionData->data;
    }

    /**
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data)
    {
        try {
            $sessionSQL = sprintf(
                'insert into `%s`.`%s` (`id`, `site`, `ip`, `agent`, `activity`, `data`) values (?, ?, ?, ?, ?, ?) on duplicate key update `activity` = ?, `data` = ?',
                $this->database,
                $this->table
            );

            if (!($statement = $this->databaseResource->prepare($sessionSQL))) {
                throw new \Exception("Failed to prepare statement!");
            }

            $activity = date('Y-m-d H:i:s');
            $ip = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            $agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

            if (!$statement->bind_param(
                'ssssssss',
                $id,
                $this->siteName,
                $ip,
                $agent,
                $activity,
                $data,
                $activity,
                $data
            )) {
                throw new \Exception("Failed to bind parameters to statement!");
            }

            $statement->execute();

            $result = $statement->affected_rows;

            $statement->close();

            return ($result > 0);

        } catch (\Exception $exception) {
            //We can return false
        }

        return false;
    }

    /**
     * Session Destroy
     *
     * @see Base::destroy()
     */
    public function destroy($id)
    {
        try {
            $sessionSQL = sprintf(
                'delete from `%s`.`%s` where `id` = ? and `site` = ? limit 1',
                $this->database,
                $this->table
            );

            $statement = $this->databaseResource->prepare($sessionSQL);

            $statement->bind_param('ss', $id, $this->siteName);

            $statement->execute();

            $statement->close();

            return true;

        } catch (\Exception $exception) {
            // We can return false
        }

        return false;
    }

    /**
     * @param $field
     * @param $value
     * @return bool
     */
    public function deleteByDataFieldValue($field, $value)
    {
        $input = '%s:' . strlen($field) . ':"' . $field . '"';
        if (is_string($value)) {
            $input .= ';s:' . mb_strlen($value) . ':"' . $value . '"%';
        } elseif (is_integer($value)) {
            $input .= ';i:' . $value . '%';
        } else {
            return false;
        }

        $num_rows = 0;

        try {
            $sessionSQL = sprintf(
                'delete from `%s`.`%s` where `data` like ? and `site` = ? limit 1',
                $this->database,
                $this->table
            );

            $statement = $this->databaseResource->prepare($sessionSQL);

            $statement->bind_param('ss', $input, $this->siteName);

            $statement->execute();

            $num_rows = $statement->affected_rows;

            $statement->close();

        } catch (\Exception $exception) {
            // Log and return
        }

        return $num_rows ? true : false;
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
            $sessionSQL = sprintf(
                'delete from `%s`.`%s` where `activity` <= ?',
                $this->database,
                $this->table
            );

            $statement = $this->databaseResource->prepare($sessionSQL);

            $cutOffTimestamp = $cutOff->format('Y-m-d H:i:s');
            $statement->bind_param('s', $cutOffTimestamp);

            $statement->execute();

            $statement->close();

            return true;
        } catch (\Exception $exception) {
            // Log and return false
        }

        return false;
    }
}