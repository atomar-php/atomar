<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

/**
 * Class CIndex is the default site index
 * @package atomar\controller
 */
class Index extends Controller {
    function GET($matches = array()) {
        Auth::authenticate();
        // render page
        echo $this->renderView('@atomar/views/index.html');
    }

    /**
     * Process POST requests
     * @param array $matches the matched patterns from the route
     */
    function POST($matches = array()) {
        $this->GET($matches);
    }
}