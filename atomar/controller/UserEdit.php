<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Lightbox;

class UserEdit extends Lightbox {

    function GET($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_users')) {
            set_error('You are not authorized to edit users');
            $this->redirect('/');
        }

        // load user
        $user = \R::load('user', $matches['id']);

        // extra authentication if this is the super user profile.
        if (Auth::is_super($user) && !Auth::is_super()) {
            set_error('You are not authorized to edit the super user.');
            $this->redirect('/');
        }

        if ($user->id) {
            // don't allow users to change their own role
            if (Auth::$user->id != $user['id']) {
                $roles = \R::find('role', 'slug!=?', array('super'));

                $user_roles = array();
                foreach ($roles as $role) {
                    $role = $role->export();
                    if ($role['id'] == $user['role']['id']) {
                        $role['selected'] = 'selected';
                    }
                    $user_roles[] = $role;
                }
            }

            // configure lightbox
            $this->width(400);
            $this->header('Edit User <small>' . $user['username'] . '</small>');

            // render page
            echo $this->renderView('admin/modal.user.edit.html', array(
                'user' => $user,
                'roles' => $user_roles,
                'is_admin' => Auth::is_admin() || Auth::is_super()
            ));
        } else {
            // close the lightbox
            set_notice('Unknown user.');
            $this->redirect();
        }
    }

    function POST($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_users')) {
            set_error('You are not authorized to edit users');
            $this->redirect('/');
        }

        // load user
        $user = \R::load('user', $matches['id']);

        // extra authentication if this is the super user profile.
        if (Auth::is_super($user) && !Auth::is_super()) {
            set_error('You are not authorized to edit the super user.');
            $this->redirect('/');
        }
        $email = $_POST['email'];
        $role_id = isset($_POST['role']) ? $_POST['role'] : false;

        if ($user->id) {
            if (!is_email($email)) {
                // invalid email
                set_error('Invalid email address');
                $this->redirect();
            } else {
                // update user fields
                $user->email = $email;
                if (!store($user)) {
                    set_error($user->errors());
                } else {
                    // set role
                    if (Auth::$user->id != $user['id'] && $role_id) {
                        $role = \R::load('role', $role_id);
                        if ($role->id) {
                            $role->ownUser[] = $user;
                            if (!store($role)) {
                                set_error($role->errors());
                            }
                        } else {
                            set_error('Unknown role. User role was not changed.');
                        }
                    }
                    set_success('User has been updated');
                }
                $this->redirect();
            }
        } else {
            // invalid user id
            set_error('Unknown user id');
            $this->redirect();
        }
    }

    /**
     * This method will be called before GET, POST, and PUT when the lightbox is returned to e.g. when using lightbox.dismiss_url or lightbox.return_url
     */
    function RETURNED() {

    }
}