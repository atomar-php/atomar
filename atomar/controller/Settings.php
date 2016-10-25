<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class Settings extends Controller {

    function GET($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_site')) {
            set_error('You are not authorized to edit settings');
            self::go('/');
        }
        $settings = \R::findAll('setting', ' ORDER BY name ASC ');
        echo $this->renderView('admin/settings.html', array(
            'settings' => $settings
        ));
    }

    function POST($matches = array()) {
        self::go('/atomar/settings/');
    }
}