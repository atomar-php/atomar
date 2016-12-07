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
        // TODO: use the proper controller render method
        $site_name = Atomar::$config['site_name'];
        $message = <<<HTML
  <p class="text-center">
     $site_name is currently being updated and will be back online shortly.
  </p>
HTML;
        echo Templator::render_error('Site Maintenance', $message);
    }
}