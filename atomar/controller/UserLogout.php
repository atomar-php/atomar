<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class UserLogout extends Controller {
    function GET($matches = array()) {
        Auth::logout();
        $this->go('/');
    }

    /**
     * Process POST requests
     * @param array $matches the matched patterns from the route
     */
    function POST($matches = array()) {
        $this->GET($matches);
    }
}