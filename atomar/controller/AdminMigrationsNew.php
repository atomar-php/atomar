<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\Atomar;
use atomar\core\Lightbox;
use atomar\core\Templator;

class AdminMigrationsNew extends Lightbox {
    function GET($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_extensions')) {
            set_error('You are not authorized to create migrations');
            $this->redirect('/');
        }

        if (!Atomar::$config['debug']) {
            set_notice('Migrations can only be created while in debug mode');
            $this->dismiss();
        }

        Templator::$js_onload[] = <<<JS
$('[data-validate]').each(function() {
  var v = new Validate($(this));
});
JS;

        // configure lightbox
        $this->header('New Migration');

        echo $this->render_view('admin/modal.migration.create.html');
    }

    function POST($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_extensions')) {
            set_error('You are not authorized to create migrations');
            $this->redirect('/');
        }

        if (!Atomar::$config['debug']) {
            set_notice('Migrations can only be created while in debug mode.');
            $this->dismiss();
        }

        $from = trim($_POST['from']);
        $to = trim($_POST['to']);

        $migration_dir = Atomar::atomar_dir() . '/atomar/migration/';
        $class = 'migration_' . md5($from . '_' . $to);
        $file = $migration_dir . $from . '_' . $to . '.php';

        if (!is_dir($migration_dir)) {
            mkdir($migration_dir);
        }

        if (!file_exists($file)) {
            $php_migration = <<<PHP
<?php

namespace rapport_core\migration;

use atomar\core\Migration;

/**
 * Migration from Atomar version $from to $to
 */
class {$class} extends Migration {

    public function run() {
        // prepare sql
        \$sql = <<<SQL
-- TODO: implement db migration
SQL;

        // perform updates
        \R::begin();
        try {
            \R::exec(\$sql);
            \R::commit();
            return true;
        } catch (\Exception \$e) {
            \R::rollback();
            log_error('Migration from Atomar version $from to $to failed', \$e->getMessage());
            return false;
        }
    }
}
PHP;
            file_put_contents($file, $php_migration);

            set_success('A new migration stub has been generated.');
        } else {
            set_error('That migration already exists.');
        }
        $this->redirect('/admin/extensions');
    }

    /**
     * This method will be called before GET, POST, and PUT when the lightbox is returned to e.g. when using lightbox.dismiss_url or lightbox.return_url
     */
    function RETURNED() {

    }
}