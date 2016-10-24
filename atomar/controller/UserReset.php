<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;
use atomar\core\Templator;

/**
 * This controller handles resetting a user's password
 * TODO: this should be an admin function
 * Class UserReset
 * @package atomar\controller
 */
class UserReset extends Controller {
    function GET($matches = array()) {
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
.form-signin .form-signin-heading {
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
.form-signin #field-password {
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  margin-bottom: -1px;
}
.form-signin #field-password2 {
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
CSS;

        $token = $_GET['token'];
        // Validate token
        if (!Auth::validate_pw_reset_token($token)) {
            // Invalid token
            set_error('That password reset link is no longer valid.');
            $this->go('/');
        } else {
            // Display new password form.
            echo $this->renderView('user/password.reset.html', array(
                'classes' => 'narrow',
                'token' => $token
            ));
        }
    }

    function POST($matches = array()) {
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
.form-signin .form-signin-heading {
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
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  margin-bottom: -1px;
}
.form-signin #field-password {
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
CSS;

        $token = $_POST['token'];
        $password = $_POST['password'];
        $password2 = $_POST['password2'];
        $id = Auth::validate_pw_reset_token($token);
        if (!$id) {
            // Invalid token
            $this->go('/');
        } else if ($password != $password2) {
            // Passwords do not match
            set_error('Passwords do not match.');
            echo $this->renderView('user/password.reset.html', array(
                'token' => $token
            ));
        } else {
            // Update password
            $user = \R::load('user', $id);
            if (!Auth::set_password($user, $password)) {
                // Error updating password
                set_error('Your password could not be changed at this time. Please try again later.');
                echo $this->renderView('user/password.reset.html', array(
                    'token' => $token,
                    'id' => $id
                ));
            } else {
                set_success('Your password has been changed.');
            }
            Auth::destroy_pw_reset_token($user);
            Auth::login($user->username, $password);
            $this->go('/');
        }
    }
}