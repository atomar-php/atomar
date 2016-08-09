<?php

namespace model;

use atomar\core\CoreBeanModel;

class User extends CoreBeanModel {
    function __construct() {
        parent::__construct();
        $this->register_property('username');
        $this->register_property('email');
        $this->register_property('first_name');
        $this->register_property('last_name');
        $this->register_property('phone');
        $this->register_property('notes');
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

    public function open() {
        $this->notes = str_replace("\\'", "'", $this->notes);
        $this->notes = str_replace('\"', '"', $this->notes);
    }

    public function update() {
        $this->security_check(); // perform core security checks

        if (!isset($this->username)) $this->kill('The username is required');
        if (!isset($this->pass_hash)) $this->kill('The password hash is required');
        if (!isset($this->email)) $this->kill('The email is required');
        if (!isset($this->first_name)) $this->first_name = '';
        if (!isset($this->last_name)) $this->last_name = '';
        if (!isset($this->phone)) $this->phone = '';
        if (!isset($this->notes)) $this->notes = '';
        if (!isset($this->is_enabled)) $this->is_enabled = '1';

        // check for duplicates
        $conflict = \R::findOne('user', '(username=:username OR email=:email) AND id<>:id LIMIT 1', array(
            ':username' => $this->username,
            ':email' => $this->email,
            ':id' => $this->id
        ));
        if ($conflict) {
            if ($conflict->username == $this->username) $this->kill('Username already exists');
            if ($conflict->email == $this->email) $this->kill('Email already exists');
        }
    }

    public function delete() {
        if ($this->role->slug == 'super') {
            $this->kill('The super user cannot be deleted!');
        }
    }
}