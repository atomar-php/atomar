<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class Users extends Controller {
    function GET($matches = array()) {
        Auth::authenticate('administer_users');

        $sql_users = <<<SQL
SELECT
  `u`.*,
  `r`.`name` AS `role`
FROM
  `user` AS `u`
LEFT JOIN
  `role` AS `r` ON `r`.`id`=`u`.`role_id`
ORDER BY
  `u`.`email`
SQL;
        $users = \R::getAll($sql_users);

        echo $this->renderView('@atomar/views/admin/users.html', array(
            'users' => $users
        ));
    }

    function POST($matches = array()) {
        $this->go('/atomar/users/');
    }
}