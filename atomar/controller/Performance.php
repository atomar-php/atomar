<?php

namespace atomar\controller;

use atomar\Atomar;
use atomar\core\Auth;
use atomar\core\Controller;

class Performance extends Controller {
    function GET($matches = array()) {
        Auth::authenticate('administer_performance');

        // render page
        echo $this->renderView('admin/performance.html', array(
            'toggle_css' => Atomar::get_system('cache_css', false) ? '0' : '1',
            'toggle_js' => Atomar::get_system('cache_js', false) ? '0' : '1'
        ));
    }

    function POST($matches = array()) {
        $this->go('/atomar/performance');
    }
}