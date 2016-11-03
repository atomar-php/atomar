<?php

namespace atomar\core;

/**
 * This is a helper class to create read only arrays
 *
 */
class ReadOnlyArray implements \ArrayAccess {
    private $container = array();
    // once this is set to true the array will be read only.
    private $is_locked = false;

    public function __construct(array $array) {
        $this->container = $array;
    }

    public function lock() {
        $this->is_locked = true;
    }

    public function offsetSet($offset, $value) {
        if ($this->is_locked) {
            throw new \Exception('Read-only');
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset) {
        if (!array_key_exists($offset, $this->container)) {
            if ($this->is_locked) {
                throw new \Exception('Undefined offset "' . $offset . '"');
            } else {
                return null;
            }
        }
        return $this->container[$offset];
    }
}
