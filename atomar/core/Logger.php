<?php

namespace atomar\core;


class Logger {


    /**
     * Logs error messages to the database
     * @param string $message the error message
     * @param mixed $data optional data that will be encoded and stored as json
     */
    public static function log_error($message, $data = array()) {
        self::log_message('error', $message, $data);
    }

    /**
     * Logs warning messages to the database
     * @param string $message the warning message
     * @param mixed $data optional data that will be encoded and stored as json
     */
    public static function log_warning($message, $data = array()) {
        return self::log_message('warning', $message, $data);
    }

    /**
     * Logs notice messages to the database
     * @param string $message the notice message
     * @param mixed $data optional data that will be encoded and stored as json
     */
    public static function log_notice($message, $data = array()) {
        return self::log_message('notice', $message, $data);
    }

    /**
     * Logs success messages to the database
     * @param string $message the success message
     * @param mixed $data optional data that will be encoded and stored as json
     */
    public static function log_success($message, $data = array()) {
        return self::log_message('success', $message, $data);
    }


    /**
     * Utility for logging messages to the db. Do not use this manually
     *
     * @param string $type the type of message to log
     * @param string $message the success message
     * @param mixed $data optional data
     */
    private static function log_message($type, $message, $data = array()) {
        $log = \R::dispense('log');
        $log->message = $message;
        $log->data = json_encode($data);
        $log->type = $type;
        $log->created_at = db_date();
        $log->access_id = isset($_SESSION['access_id']) ? $_SESSION['access_id'] : '';
        return store($log);
    }
}