<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Lightbox;

class AdminUsersCreate extends Lightbox {
    function GET($matches = array()) {
        if (!Auth::has_authentication('administer_users')) {
            set_error('You are not authorized to edit users');
            $this->redirect('/');
        }

        // configure lightbox
        $this->width(750);
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
        echo $this->renderView('admin/modal.user.create.html', $args);
    }

    function POST($matches = array()) {
        if (!Auth::has_authentication('administer_users')) {
            set_error('You are not authorized to edit users');
            $this->redirect('/');
        }

        $user = \R::dispense('user');
        $user->username = $_POST['username'];
        $user->first_name = $_POST['first_name'];
        $user->last_name = $_POST['last_name'];
        $user->email = $_POST['email'];
        $user->phone = $_POST['phone'];
        $user->notes = $_POST['notes'];
        $user->is_enabled = $_POST['enabled'] == 'on' ? '1' : '0';
        $password = $_POST['password'];
        $role_id = $_POST['role'];

        $notify = $_POST['notify'] == 'on' ? 1 : 0;

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
                $this->redirect('/admin/users');
            } else {
                if ($notify) {
                    if (!Auth::make_pw_reset_token($user)) {
                        // Reset key could not be created
                        set_error('Failed to create password reset token. Email not sent.');
                        set_success('User created!');
                        $this->redirect('/admin/users');
                    } else {
                        // Success
                        $expires = time_until_date(strtotime($user->pass_reset_expires_at));
                        email($user->email, 'Account Created', '', array(
                            'recipient' => $user,
                            'token' => $user->pass_reset_token,
                            'expires' => $expires,
                            'template' => 'new_account_by_admin.html'
                        ));
                        set_success('User created! A notification email has been sent to the user.');
                        $this->redirect('/admin/users');
                    }
                } else {
                    set_success('User created!');
                    $this->redirect('/admin/users');
                }
            }
        } else {
            // user exists
            if ($existing_user->username == $user->username) {
                set_error('That username is not available.');
            }
            if ($existing_user->email == $user->email) {
                set_error('That email is not available.');
            }
            $this->redirect('/admin/users');
        }
    }

    /**
     * This method will be called before GET, POST, and PUT when the lightbox is returned to e.g. when using lightbox.dismiss_url or lightbox.return_url
     */
    function RETURNED() {

    }
}