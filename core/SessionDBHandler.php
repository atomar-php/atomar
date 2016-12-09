<?php

namespace atomar\core;

/**
 * The SessionDBHandler provides seamless database
 * integration with the php session. With this class you may
 * use the php session variables like normal and the session will
 * be stored in the database.
 */
class SessionDBHandler implements \SessionHandlerInterface{
    public $maxTime;

    /**
     * Close the session
     * @link http://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function close() {
        return true;
    }

    /**
     * Destroy a session
     * @link http://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $session_id The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($session_id) {
        $session = \R::findOne('session', 'session_id=:id', array(':id' => $session_id));
        if ($session) {
            \R::trash($session);
        }
        return true;
    }

    /**
     * Cleanup old sessions
     * @link http://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($maxlifetime) {
        $expires = time() - $maxlifetime;
        $sessions = \R::find('session', 'last_activity < :time', array(
            ':time' => $expires
        ));
        \R::trashAll($sessions);
        return true;
    }

    /**
     * Initialize session
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $name) {
        return true;
    }

    /**
     * Read session data
     * @link http://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function read($session_id) {
        $session = \R::findOne('session', 'session_id=:id', array(':id' => $session_id));
        if ($session) {
            return $session->data;
        } else {
            return '';
        }
    }

    /**
     * Write session data
     * @link http://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $session_id The session id.
     * @param string $session_data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function write($session_id, $session_data) {
        $session = \R::findOne('session', 'session_id=:id', array(':id' => $session_id));
        if (!$session) {
            $session = \R::dispense('session');
            $session->session_id = $session_id;
        }
        $session->last_activity = time();
        $session->data = $session_data;

        // set the user id the first time the session is created
        if (!$session->user_id) {
            $pieces = explode(';', $session_data);
            foreach ($pieces as $part) {
                try {
                    $key_value = explode('|', $part);
                    if (count($key_value) == 2) {
                        if ($key_value[0] == 'user_id') {
                            list($type, $length, $val) = explode(':', $key_value[1]);
                            $session->user_id = trim($val, '"');
                            break;
                        }
                    } else {
                        // part was malformed
                    }

                } catch (\Exception $e) {
                    // part was malformed
                }
            }
        }
        return \R::store($session) > 0;
    }
}