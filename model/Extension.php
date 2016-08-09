<?php

namespace model;

use atomar\core\CoreBeanModel;

class Extension extends CoreBeanModel {
    function __construct() {
        parent::__construct();
        $this->register_property('is_enabled');
        $this->register_property('is_update_pending');
        $this->register_property('slug');
        $this->register_property('name');
        $this->register_property('description');
        $this->register_property('version');
        $this->register_property('installed_version');
        $this->register_property('core');
        $this->register_property('dependencies');
        $this->register_property('is_supported');
        $this->register_property('shared[A-Z].*');
        $this->register_property('own[A-Z].*');
    }

    /**
     * Sets the extension dependencies
     * @param $dependencies
     */
    public function set_dependencies($dependencies) {
        $this->bean->is_missing_dependencies = '0';
        $this->bean->sharedExtensionList = array();
        foreach($dependencies as $key => $value) {
            $ext = \R::findOne('extension', 'slug=?', array($value));
            if($ext !== null) {
                $this->bean->sharedExtensionList[] = $ext;
            } else {
                $this->bean->is_missing_dependencies = '1';
            }
        }
    }
}