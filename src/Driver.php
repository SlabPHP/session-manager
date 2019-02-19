<?php
/**
 * Slab custom session driver
 *
 * Required configuration options:
 * @param $config ->session[handler] Session handler class name eg. Database, File
 *
 * @author Eric
 * @package Slab
 * @subpackage Utilities
 */
namespace Slab\Session;

class Driver implements \Slab\Components\SessionDriverInterface
{
    /**
     * Handler for sessions
     *
     * @var \SessionHandlerInterface
     */
    private $handler;

    /**
     * Session token
     *
     * @var string
     */
    private $sessionToken;

    /**
     * User data
     *
     * @var mixed[]
     */
    private $userData = [];

    /**
     * Flash data marked for deletion will be here
     *
     * @var mixed[]
     */
    private $killList = [];

    /**
     * Session corruption occurred
     *
     * @var boolean
     */
    private $sessionCorruption = false;

    /**
     * @var string
     */
    private $sessionCookieName = 'session';

    /**
     * @var int
     */
    private $sessionExpiration = 86400;

    /**
     * @var string
     */
    protected $sessionCookiePath = '/';

    /**
     * @var string
     */
    protected $sessionCookieDomain = '';

    /**
     * @var bool
     */
    protected $sessionCookieSecure = false;


    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @param \SessionHandlerInterface $handler
     * @return $this
     */
    public function setHandler(\SessionHandlerInterface $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Set cookie parameters
     *
     * @param string $cookieName
     * @param int $expiration
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @return $this
     */
    public function setCookieParameters($cookieName, $expiration, $path = '/', $domain = '', $secure = false)
    {
        $this->sessionCookieName = $cookieName;
        $this->sessionExpiration = $expiration;
        $this->sessionCookiePath = $path;
        $this->sessionCookieDomain = $domain;
        $this->sessionCookieSecure = $secure;

        return $this;
    }

    /**
     * @param \Psr\Log\LoggerInterface $logInterface
     * @return $this
     */
    public function setLog(\Psr\Log\LoggerInterface $logInterface)
    {
        $this->log = $logInterface;

        return $this;
    }

    /**
     * Generate a user token, or retrieve it from cookie
     */
    private function performTokenInitialization()
    {
        if (!empty($_COOKIE[$this->sessionCookieName])) {
            $this->sessionToken = $_COOKIE[$this->sessionCookieName];

            if (preg_match('#[a-f0-9]{40}#', $this->sessionToken)) {
                return;
            }
        }

        $this->sessionToken = sha1(uniqid('slabphp', true));

        if (headers_sent()) {
            if ($this->log) {
                $this->log->critical('Failed to save session cookie, headers already sent.');
            }
            return;
        }

        if (!setcookie(
            $this->sessionCookieName,
            $this->sessionToken,
            !empty($this->sessionExpiration) ? (time() + $this->sessionExpiration) : 0,
            $this->sessionCookiePath,
            $this->sessionCookieDomain,
            $this->sessionCookieSecure
        )) {
            if ($this->log) {
                $this->log->critical('Failed to save session cookie!');
            }
        }
    }

    /**
     * @param null $variable
     * @return mixed|\mixed[]|null
     */
    public function get($variable = NULL)
    {
        if ($variable == NULL) {
            return $this->userData;
        }

        if (!empty($this->userData[$variable])) {
            return $this->userData[$variable];
        }

        return NULL;
    }

    /**
     * Set user data
     *
     * @param string $variable
     * @param mixed $value
     */
    public function set($variable, $value = null)
    {
        if (is_array($variable)) {
            $this->userData = array_merge($this->userData, $variable);
        } else if (is_object($variable)) {
            $this->userData = array_merge($this->userData, (array)$variable);
        } else {
            if ($value === null) {
                unset($this->userData[$variable]);
            } else {
                $this->userData[$variable] = $value;
            }
        }
    }

    /**
     * @param $variable
     * @return bool
     */
    public function delete($variable)
    {
        unset($this->userData[$variable]);

        return true;
    }

    /**
     * Destroy the session
     */
    public function destroy()
    {
        if (empty($this->sessionToken)) return;

        $this->userData = array();

        $this->handler->destroy($this->sessionToken);

        setcookie($this->sessionCookieName, null, $this->sessionExpiration, '/');

        $this->sessionToken = NULL;
    }

    /**
     * @return bool
     */
    private function isRobot()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) return true;

        //Quick generic bot regex
        if (preg_match('#(bot|page speed|slurp|yahoo|jeeves|crawler|spider|infoseek|aboundex|yandex|ezooms|spider)#', $_SERVER['HTTP_USER_AGENT']))
        {
            return true;
        }

        return false;
    }

    /**
     * @return null|string
     * @throws Exceptions\Irreparable
     */
    public function start()
    {
        if ($this->isRobot()) {
            return null;
        }

        //Initialize a token
        $this->performTokenInitialization();

        if (empty($this->sessionToken)) {
            if (!empty($this->log))
            {
                $this->log->error("Could not create a session token for user " . $_SERVER['REMOTE_ADDR'] . ' and user agent ' . $_SERVER['HTTP_USER_AGENT']);
            }

            return null;
        }

        //Tell the handler to open up
        if (!$this->handler->open(false, $this->sessionToken)) {
            if (!empty($this->log))
            {
                $this->log->error("Could not open session token " . $this->sessionToken . " for user " . $_SERVER['REMOTE_ADDR'] . ' and user agent ' . $_SERVER['HTTP_USER_AGENT']);
            }

            unset($this->sessionToken);

            return null;
        }

        //Try to load user data
        try
        {
            $this->userData = $this->decode($this->handler->read($this->sessionToken));
        }
        catch (\Slab\Session\Exceptions\Corruption $exception)
        {
            if (!$this->sessionCorruption) {

                $this->sessionCorruption = true;

                if (!empty($this->log)) {
                    $this->log->error("Session data corrupted, trying again with a fresh session.", $exception);
                }

                $this->destroy();

                return $this->start();
            }

            if (!empty($this->log)) {
                $this->log->error("Session data corrupted irreparably.", $exception);
            }

            $this->destroy();

            throw new Exceptions\Irreparable("A session could not be started.");

        } catch (\Exception $exception) {
            if (!empty($this->log)) {
                $this->log->error("Failed to read session data.", $exception);
            }

            throw new Exceptions\Irreparable("A session could not be started.");
        }

        //If there's no user data but there's no error, just initialize it empty
        if (empty($this->userData)) $this->userData = array();

        $this->markFlashDataForDeletion();

        return $this->sessionToken;
    }


    /**
     * Adds vars to the kill list, these variables won't be written to the session data again but are available for reading
     */
    private function markFlashDataForDeletion()
    {
        foreach ($this->userData as $variable => $value) {
            if ($variable[0] == '@') {
                $this->killList[] = $variable;
            }
        }
    }

    /**
     * Perform actual flash data kill
     */
    private function killMarkedFlashData()
    {
        foreach ($this->killList as $killVariable) {
            if (isset($this->userData[$killVariable])) {
                unset($this->userData[$killVariable]);
            }
        }
    }

    /**
     * Commit any session changes
     */
    public function __destruct()
    {
        if (empty($this->sessionToken)) return;

        $this->killMarkedFlashData();

        $this->commit();

        $this->handler->gc($this->sessionExpiration);

        $this->handler->close();
    }

    /**
     * Commit session to db
     */
    public function commit()
    {
        if (empty($this->sessionToken)) return;

        $this->handler->write($this->sessionToken, $this->encode($this->userData));
    }

    /**
     * Decode data
     *
     * @param string $input
     * @return boolean
     */
    private function decode($input)
    {
        return unserialize($input);
    }

    /**
     * Encode data
     *
     * @param string $input
     * @return boolean
     */
    private function encode($input)
    {
        return serialize($input);
    }

    /**
     * Return the current handler in case it has some special functions you need to access
     *
     * @return \SessionHandlerInterface
     */
    public function getHandler()
    {
        return $this->handler;
    }

}
