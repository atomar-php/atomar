<?php

namespace model;

use atomar\core\CoreBeanModel;

class Log extends CoreBeanModel {
    function __construct() {
        parent::__construct();
        $this->register_property('message');
        $this->register_property('data');
        $this->register_property('type');
        $this->register_property('created_at');
        $this->register_property('access_id');
    }
}