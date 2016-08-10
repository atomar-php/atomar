<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\core\Controller;

class AdminUsers extends Controller {
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
  `u`.`first_name`,
  `u`.`last_name`,
  `u`.`username`
SQL;
        $users = \R::getAll($sql_users);

        echo $this->renderView('admin/users.html', array(
            'users' => $users
        ));
    }

    function POST($matches = array()) {
        $this->go('/admin/users/');
    }
}