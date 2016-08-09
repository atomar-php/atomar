<?php

namespace atomar\core;

/**
 * This is a simple wrapper to the RedBean model that provides
 * some extra functionality.
 */
class BeanModel extends \RedBean_SimpleModel {
    private $_error_messages;

    /**
     * This utility function will return the enum values for a field.
     * It is assumed the field is an actual enum.
     * @param string $field the property of the model from which the enum values should be fetched.
     * @return array an array of enum values available in the requested field.
     * @throws \Exception
     */
    public function get_enum($field) {
        throw new \Exception("I'm not sure if this enum method works correctly.");
        // TODO: I'm not sure if passing the table name in through RedBean works.
        $sql_enum = <<<MYSQL
SHOW COLUMNS FROM
  `?`
WHERE
  `Field` = ?
MYSQL;
        $result = \R::getRow($sql_enum, array(
            $this->bean->getMeta('type'),
            $field
        ));
        preg_match('/^enum\((.*)\)$/', $result['Type'], $matches);
        $enum = array();
        foreach (explode(',', $matches[1]) as $value) {
            $enum[] = trim($value, "'");
        }
        return $enum;
    }

    /**
     * Use this to access error messages
     * @return string returns the error message that has been stored in this bean.
     */
    public function errors() {
        return $this->$_error_messages;
    }

    /**
     * Call this function to stop the process and store the error message
     * @param string @message a message explaining why the process was killed.
     * @throws ModelException
     */
    protected function kill($message) {
        $this->$_error_messages = $message;
        throw new ModelException();
    }

    /**
     * Unescape single and double quotes in the string because RedBean automatically escapes them.
     * @param string $value the string to unescape
     * @return string the escaped string
     */
    protected function unescape($value) {
        $value = str_replace("\'", "'", $value);
        $value = str_replace('\"', '"', $value);
        return $value;
    }
}

/**
 * This is a custom exception class for beans.
 */
class ModelException extends \Exception {
    function __construct() {
        parent::__construct();
    }
}