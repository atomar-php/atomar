<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Lightbox;

class UserAdd extends Lightbox {
    function GET($matches = array()) {
        if (!Auth::has_authentication('administer_users')) {
            set_error('You are not authorized to edit users');
            $this->redirect('/');
        }

        // configure lightbox
        $this->width(400);
        $this->header('New User');

        $this->render_form(array(
            'enabled' => 'checked'
        ));
    }

    function render_form($values = array()) {
        // load all  the roles except for the super
        $roles = \R::find('role', 'slug!=?', array('super'));

        $user_roles = array();
        foreach ($roles as $role) {
            $role = $role->export();
            if ($role['id'] == $values['role']) {
                $role['selected'] = 'selected';
            }
            $user_roles[] = $role;
        }

        // render page
        $args = array_merge($values, array(
            'roles' => $user_roles
        ));
        echo $this->renderView('@atomar/views/admin/modal.user.edit.html', $args);
    }

    function POST($matches = array()) {
        if (!Auth::has_authentication('administer_users')) {
            set_error('You are not authorized to edit users');
            $this->redirect('/');
        }

        $user = \R::dispense('user');
        $user->email = $_POST['email'];
        $user->is_enabled = 1;
        $password = $_POST['password'];
        $role_id = $_POST['role'];
        $role = \R::load('role', $role_id);

        // check for unique username and email
        // TODO: let the model handle all the validation.
        $existing_user = \R::findOne('user', 'username=:username OR email=:email LIMIT 1', array(
            ':username' => $user->username,
            ':email' => $user->email
        ));
        if (!$existing_user) {
            $user = Auth::register($user, $password, $role);
            if (!$user) {
                // Creation failed
                set_error('Failed to create user');
                $this->redirect('/atomar/users');
            } else {
                set_success('User created!');
                $this->redirect('/atomar/users');
            }
        } else {
            // user exists
            if ($existing_user->username == $user->username) {
                set_error('That username is not available.');
            }
            if ($existing_user->email == $user->email) {
                set_error('That email is not available.');
            }
            $this->redirect('/atomar/users');
        }
    }

    /**
     * This method will be called before GET, POST, and PUT when the lightbox is returned to e.g. when using lightbox.dismiss_url or lightbox.return_url
     */
    function RETURNED() {

    }
}