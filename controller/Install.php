<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;
use atomar\Atomar;
use atomar\core\Logger;
use atomar\core\Templator;

class Install extends Controller {
    private static $css = '';

    function __construct() {
        parent::__construct();
        self::$css = <<<CSS
body {
  padding-top: 40px;
  padding-bottom: 40px;
  background-color: #eee;
}
.form-install {
  max-width: 330px;
  padding: 15px;
  margin: 0 auto;
}
.form-install .form-install-heading, .form-install .checkbox {
  margin-bottom: 10px;
}
.form-install .form-control {
  position: relative;
  font-size: 16px;
  height: auto;
  padding: 10px;
  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;
}
.form-install #field-username {
  margin-bottom: -1px;
  border-radius: 0;
}
.form-install #field-email {
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  margin-bottom: -1px;
}
.form-install #field-password {
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
.form-install .checkbox {
  font-weight: normal;
}
CSS;
    }

    function GET($matches = array()) {
        Templator::$css_inline[] = self::$css;
        echo $this->renderView('@atomar/views/install.html', $matches);
    }

    function POST($matches = array()) {
        $full_install = $_POST['full_install'] === 'on';
        $password = $_POST['password'];
        unset($_POST['password']);
        $email = $_POST['email'];

        // validate
        if(!self::requiresInstall()) {
            throw new \Exception('The system is not eligible for installation');
        }
        if(self::isEmpty($email) || !is_email($email) || self::isEmpty($password)) {
            set_error('A valid email and password are required');
            $this->GET($_POST);
            return;
        }

        // perform full install
        if ($full_install) {
            try {
                Atomar::hookModule(new \atomar\hook\Install(), Atomar::atomar_namespace(), Atomar::atomar_dir(), null, null, false, true);
            } catch (\Exception $e) {
                set_error('Failed installing Atomar');
                $this->GET($_POST);
                return;
            }
        }

        // fetch super role
        $super_role = \R::findOne('role', 'slug="super"');
        if (!$super_role->id) {
            if($full_install) {
                set_error('The super role is missing. Perhaps something is broken?');
            } else {
                set_notice('Please perform a full install');
            }
            $this->GET($_POST);
            return;
        }

        // create user
        $user = \R::dispense('user');
        $user->email = $email;
        $user->is_enabled = 1;

        // register super user
        $user = Auth::register($user, $password, $super_role);
        if ($user) {
            // login
            if (!Auth::login($user->email, $password)) {
                $this->go('/atomar/login');
            } else {
                $this->go('/atomar');
            }
        } else {
            set_error('Super account could not be created');
            $this->GET($_POST);
            return;
        }
    }

    /**
     * Checks if a value is empty
     * @param string $value
     * @return bool
     */
    private static function isEmpty(string $value) {
        return !isset($value) || trim($value) == '';
    }

    /**
     * Checks if the system requires an install.
     * This is true if there are no users.
     *
     * @return bool
     */
    public static function requiresInstall() {
        return \R::count('user') === 0;
    }
}