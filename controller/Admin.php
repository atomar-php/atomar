<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class Admin extends Controller {
    function GET($matches = array()) {
        Auth::authenticate('administer_site');

        echo $this->renderView('admin/index.html');
    }

    /**
     * Process POST requests
     * @param array $matches the matched patterns from the route
     */
    function POST($matches = array()) {
        $this->GET($matches);
    }
}