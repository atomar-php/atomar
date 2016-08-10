<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class User extends Controller {
    function GET($matches = array()) {
        // load user
        $user = \R::load('user', $matches['id']);
        $user->role; // preload

        // require authentication
        Auth::authenticate(array(
            'permissions' => array('administer_users'),
            'users' => array($user)
        ));

        if ($user->id) {
            // render the page
            $user = $user->export();
            if ($user['last_login'] != '') {
                $user['last_login'] = fancy_date(strtotime($user['last_login']));
            } else {
                $user['last_login'] = 'never';
            }
            echo $this->renderView('user/index.html', array(
                'user' => $user
            ));
        } else {
            set_error('Unknown user');
            echo $this->renderView('404.html');
        }
    }

    function POST($matches = array()) {
        $this->go('/user/' . $matches['id']);
    }
}