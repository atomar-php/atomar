<?php

namespace model;

use atomar\core\CoreBeanModel;

class Session extends CoreBeanModel {
    function __construct() {
        parent::__construct();
        $this->register_property('session_id');
        $this->register_property('last_activity');
        $this->register_property('data');
        $this->register_property('user_id');
    }
}