<?php

namespace atomar\controller;

use atomar\core\Controller;
use atomar\Atomar;
use atomar\core\Templator;

class Maintenance extends Controller {

    public function POST($matches = array()) {
        $this->GET();
    }

    public function GET($matches = array()) {
        $message = "We are performing some updates and will be back shortly.";
        echo Templator::render_template('maintenance.html', array(
            'title'=> Atomar::$config['site_name'],
            'message'=> $message
        ));
    }
}