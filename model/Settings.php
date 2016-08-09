<?php

namespace model;

use atomar\core\CoreBeanModel;

class Settings extends CoreBeanModel {
    function __construct() {
        parent::__construct();
        $this->register_property('name');
        $this->register_property('value');
    }
}