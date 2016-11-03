<?php

namespace atomar\core;
use atomar\Atomar;

/**
 * This is a special version of the bean model that
 * should always be used for models defined in the core.
 * The only purpose is to deter third party extensions
 * from modifying core models and possibly breaking modularity.
 */
class CoreBeanModel extends BeanModel {

    /**
     * @var array an array of all the valid properties in the model
     */
    private $_registered_properties = array();

    /**
     * Default properties are registered here.
     * Make sure to call the parent constructor.
     */
    function __construct() {
        $this->register_property('id');
    }

    /**
     * This will register a property with the list of valid properties.
     * Any property not registered will fail the security check.
     * @param string @property the property to register.
     */
    protected function register_property($property) {
        if (!in_array($property, $this->_registered_properties)) {
            $this->_registered_properties[] = $property;
        }
    }

    /**
     * Perform the security check by default.
     * If you override the update method make sure to
     * call security_check() before doing anything else.
     * @throws \Exception when the security check fails
     */
    public function update() {
        $this->security_check();
    }

    /**
     * Performs the security check and throws an exception if
     * any unknown properties have been added.
     * @throws \Exception when the security check fails
     */
    protected function security_check() {
        // only perform the security check when in debug mode.
        if (Atomar::$config['debug']) {
            foreach ($this->bean as $p => $v) {
                if (!in_array($p, $this->_registered_properties)) {
                    // perform regular expression check
                    $preg_match = false;
                    foreach ($this->_registered_properties as $k) {
                        if (preg_match('/' . $k . '/', $p)) $preg_match = true;
                    }
                    if (!$preg_match) {
                        throw new ModelException('Invalid property "' . $p . '". The ' . $this->bean->getMeta('type') . ' model is part of the core and cannot be modified.');
                    }
                }
            }
        }
    }
}