<?php

namespace model;

use atomar\core\CoreBeanModel;

/**
 * Class User represents a user account
 * @package model
 */
class User extends CoreBeanModel {
    function __construct() {
        parent::__construct();
        $this->register_property('email');
        $this->register_property('is_enabled');
        $this->register_property('pass_hash');
        $this->register_property('role');
        $this->register_property('role_id');
        $this->register_property('last_ip');
        $this->register_property('last_user_agent');
        $this->register_property('last_login');
        $this->register_property('pass_reset_token');
        $this->register_property('pass_reset_expires_at');
        $this->register_property('last_activity');
        $this->register_property('auth_token');
        $this->register_property('own[A-Z].*');
        $this->register_property('shared[A-Z].*');
    }

    public function update() {
        $this->security_check(); // perform core security checks

        if (!isset($this->pass_hash)) $this->kill('The password hash is required');
        if (!isset($this->email)) $this->kill('The email is required');
        if (!isset($this->is_enabled)) $this->is_enabled = 1;

        // check for duplicates
        $conflict = \R::findOne('user', 'email=:email AND id<>:id LIMIT 1', array(
            ':email' => $this->email,
            ':id' => $this->id
        ));
        if ($conflict) $this->kill('Email already used');
    }

    public function delete() {
        if ($this->role->slug == 'super') {
            $this->kill('The super user cannot be deleted!');
        }
    }
}