<?php

namespace model;

use atomar\core\CoreBeanModel;

class Role extends CoreBeanModel {

    function __construct() {
        parent::__construct();
        $this->register_property('name');
        $this->register_property('slug');
        $this->register_property('ownUser');
        $this->register_property('sharedPermission');
    }

    public function update() {
        $this->security_check(); // perform core security checks

        // generate slug
        $this->slug = human_to_machine($this->name);

        // validate
        $conflict = \R::findOne('role', '(name=:name OR slug=:slug) AND id<>:id LIMIT 1', array(
            ':name' => $this->name,
            ':id' => $this->id,
            ':slug' => $this->slug
        ));
        if ($conflict) {
            $this->kill('Duplicate entry.');
        }
    }
}