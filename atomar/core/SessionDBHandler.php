<?php

namespace atomar\core;

/**
 * The SessionDBHandler provides seamless database
 * integration with the php session. With this class you may
 * use the php session variables like normal and the session will
 * be stored in the database.
 */
class SessionDBHandler {
    public $maxTime;

    public function __construct() {
        session_set_save_handler(array(
            $this,
            '_open'
        ), array(
            $this,
            '_close'
        ), array(
            $this,
            '_read'
        ), array(
            $this,
            '_write'
        ), array(
            $this,
            '_destroy'
        ), array(
            $this,
            '_clean'
        ));
        register_shutdown_function('session_write_close');
        return true;
    }

    public static function _open() {
        return true;
    }

    public static function _close() {
        return true;
    }

    public static function _read($id) {
        $session = \R::findOne('session', 'session_id=:id', array(':id' => $id));
        if ($session) {
            return $session->data;
        } else {
            return '';
        }
    }

    public static function _write($id, $data) {
        $session = \R::findOne('session', 'session_id=:id', array(':id' => $id));
        if (!$session) {
            $session = \R::dispense('session');
            $session->session_id = $id;
        }
        $session->last_activity = time();
        $session->data = $data;

        // set the user id the first time the session is created
        if (!$session->user_id) {
            $pieces = explode(';', $data);
            foreach ($pieces as $part) {
                try {
//                    list($key, $value) d;
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
        return \R::store($session);
    }

    public static function _destroy($id) {
        $session = \R::findOne('session', 'session_id=:id', array(':id' => $id));
        if ($session) {
            \R::trash($session);
        }
        return true;
    }

    public static function _clean($max_lifetime) {
        $expires = time() - $max_lifetime;
        $sessions = \R::find('session', 'last_activity < :time', array(
            ':time' => $expires
        ));
        \R::trashAll($sessions);
        return true;
    }
}