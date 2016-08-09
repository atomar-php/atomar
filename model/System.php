<?php

namespace model;

use atomar\core\CoreBeanModel;

class System extends CoreBeanModel {

    function __construct() {
        parent::__construct();
        $this->register_property('name');
        $this->register_property('value');
    }

    public function update() {
        $this->security_check(); // perform core security checks

        $conflict = \R::findOne('system', 'name=:name AND id<>:id LIMIT 1', array(
            ':name' => $this->name,
            ':id' => $this->id
        ));
        if ($conflict) {
            $this->kill('Duplicate entry.');
        }
    }
}