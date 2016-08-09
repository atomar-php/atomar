<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class AdminSettings extends Controller {

    function GET($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_settings')) {
            set_error('You are not authorized to edit settings');
            self::go('/');
        }
        $settings = \R::findAll('setting', ' ORDER BY name ASC ');
        echo $this->render_view('admin/settings.html', array(
            'settings' => $settings
        ));
    }

    function POST($matches = array()) {
        self::go('/admin/settings/');
    }
}