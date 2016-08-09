<?php

namespace model;

use atomar\core\CoreBeanModel;

class Permission extends CoreBeanModel {

    function __construct() {
        parent::__construct();
        $this->register_property('slug');
        $this->register_property('name');
    }

    public function update() {
        $this->security_check(); // perform core security checks

        // generate slug
        $this->name = machine_to_human($this->slug);
    }
}