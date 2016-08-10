<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;
use atomar\core\Templator;

class UserRecover extends Controller {
    function GET($matches = array()) {
        $id = $matches['id'];

        Templator::$css_inline[] = <<<CSS
body {
  padding-top: 40px;
  padding-bottom: 40px;
  background-color: #eee;
}
.form-signin {
  max-width: 330px;
  padding: 15px;
  margin: 0 auto;
}
.form-signin .checkbox {
  margin-bottom: 10px;
}
.form-signin .form-control {
  position: relative;
  font-size: 16px;
  height: auto;
  padding: 10px;
  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;
}
.form-signin #field-username {
  margin-bottom: 10px;
}
.form-signin .checkbox {
  font-weight: normal;
}
CSS;
        // Tool for admin
        if ($id) {
            $user = \R::load('user', $id);
            $user->role;  // preload

            if ($user->id) {
                Auth::authenticate(array(
                    'permissions' => array('administer_users'),
                    'users' => $user
                ));

                if (Auth::make_pw_reset_token($user)) {
                    $expires = time_until_date(strtotime($user->pass_reset_expires_at));
                    $result = email($user->email, 'Reset Password', '', array(
                        'user_id' => $user->id,
                        'token' => $user->pass_reset_token,
                        'expires' => $expires,
                        'template' => 'reset_password.html'
                    ));
                    if ($result) {
                        set_success('A password reset link has been emailed to the user.');
                    } else {
                        set_error('The password reset link could not be sent to the user!');
                    }
                } else {
                    set_error('The password reset token could not be created.');
                }
            } else {
                set_error('Unknown user.');
            }
            $this->go_back();
        } else {
            // Form for users
            echo $this->renderView('user/password.forgot.html', array(
                'classes' => 'narrow'
            ));
        }
    }

    function POST($matches = array()) {
        $username = $_POST['username'];
        // Validate username or email
        $user = \R::findOne('user', 'email=:email OR username=:username', array(
            ':email' => $username,
            ':username' => $username
        ));

        if ($user->id) {
            $user->role; // preload

            if (!Auth::make_pw_reset_token($user)) {
                // Reset key could not be created
                set_error('Something went wrong and we were unable to process your request!');
                $this->go('/user/recover');
            } else {
                // Success
                set_success('A password reset link has been sent to your email address.');
                $expires = time_until_date(strtotime($user->pass_reset_expires_at));
                email($user->email, 'Reset Password', '', array(
                    'user_id' => $user->id,
                    'token' => $user->pass_reset_token,
                    'expires' => $expires,
                    'template' => 'reset_password.html'
                ));
                $this->go('/');
            }
        } else {
            // Unknown user
            set_error('We could not find anyone by that username or email.');
            $this->go('/user/recover');
        }
    }
}