<?php

namespace model;

use atomar\core\CoreBeanModel;

class Access extends CoreBeanModel {
    function __construct() {
        parent::__construct();
        $this->register_property('user');
        $this->register_property('user_id');
        $this->register_property('accessed_at');
        $this->register_property('login_failed');
        $this->register_property('ip_address');
        $this->register_property('user_agent');
        $this->register_property('url');
    }
}