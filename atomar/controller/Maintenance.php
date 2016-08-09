<?php

namespace atomar\controller;

use atomar\core\Controller;
use atomar\Atomar;
use atomar\core\Logger;

class Maintenance extends Controller {

    public function POST($matches = array()) {
        $this->GET();
    }

    public function GET($matches = array()) {
        // Run Maintenance Mode
        if (system_get('maintenance_mode') == '1') {
            error_reporting(0);
            Atomar::run_maintenance();
        } else {
            Logger::log_warning('The maintenance controller was called when maintenance mode was not enabled');
        }
    }
} 