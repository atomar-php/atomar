<?php

namespace atomar\controller;

use atomar\core\Controller;
use atomar\core\Templator;

class ServerResponseHandler extends Controller {

    function GET($matches = array()) {
        echo Templator::render_template('@atomar/views/server_response_handler.html', array(
            'code' => $matches['code']
        ));
    }

    function POST($matches = array()) {
        // routes posts on this page back to get.
        self::GET($matches);
    }
}