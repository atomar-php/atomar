<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

/**
 * Logs the user out of the system
 * Class Logout
 * @package atomar\controller
 */
class Logout extends Controller {
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