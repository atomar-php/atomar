<?php

namespace atomar\controller;

use atomar\core\Controller;

class ExceptionHandler extends Controller {
    function GET($matches = array()) {
        $path = $_GET['path'];
        echo $this->renderView('404.html', array(
            'path' => $path
        ));
    }

    function POST($matches = array()) {

    }
}