<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;
use atomar\core\Templator;

class Login extends Controller {
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
.form-signin .form-signin-heading, .form-signin .checkbox {
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
.form-signin .checkbox {
  font-weight: normal;
}
CSS;

        echo $this->renderView('admin/login.html');
    }

    function POST($matches = array()) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        if (!Auth::login($username, $password)) {
            set_error('Login failed');
            $this->go('/user/login');
        } else {
            $this->go('/');
        }
    }
}