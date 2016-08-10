<?php

namespace atomar\controller;

use atomar\core\Auth;
use atomar\Atomar;
use atomar\core\Lightbox;
use atomar\core\Templator;

class AdminExtensionsNew extends Lightbox {
    function GET($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_extensions')) {
            set_error('You are not authorized to edit extensions');
            $this->redirect('/');
        }

        // only allow in debug mode
        if (!Atomar::$config['debug']) {
            set_notice('Extensions can only be created while in debug mode.');
            $this->dismiss();
        }

        // validate server configuration
        if (!is_writable(Atomar::extension_dir())) {
            set_notice('The extension directory is not writable');
            $this->dismiss();
        }

        $sql_extensions = <<<SQL
SELECT
  `e`.`slug` AS `key`,
  `e`.`name` AS `value`
FROM
  `extension` AS `e`
SQL;
        $extensions = \R::getAll($sql_extensions);

        Templator::$js_onload[] = <<<JS
$('[data-validate]').each(function() {
  var v = new Validate($(this));
});
$('#field-name').change(function() {
  var value = human_to_machine($(this).val(), '_');
  $('#slug').html(value);
  $('#field-slug').val(value);
}).keyup( function () {
  $(this).change();
});
JS;
        Templator::$css_inline[] = <<<CSS
#slug {
      margin: 30px 0 0 10px;
    }
CSS;

        // configure lightbox
        $this->width(600);
        $this->header('New Extension');

        echo $this->renderView('admin/modal.extension.create.html', array(
            'extensions' => $extensions
        ));
    }

    function POST($matches = array()) {
        // require authentication
        if (!Auth::has_authentication('administer_extensions')) {
            $this->redirect('You are not authorized to edit extensions');
        }

        if (!Atomar::$config['debug']) {
            set_notice('Extensions can only be created while in debug mode.');
            $this->dismiss();
        }

        $slug = $_POST['slug'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $add_rest_api = $_POST['rest_api'] == 'on';
        $add_php_api = $_POST['php_api'] == 'on';
        $dependencies = $_POST['dependencies'];

        if ($description == '') $description = 'This is an extension stub';

        $class_slug = str_replace(' ', '', ucwords(str_replace('_', ' ', $slug)));

        $path = Atomar::extension_dir() . $slug . '/';
        $js_path = $path . 'js/';
        $css_path = $path . 'css/';
        $img_path = $path . 'img/';
        $models_path = $path . 'model/';
        $views_path = $path . 'views/';
        $controllers_path = $path . 'controller/';

        if (!is_dir($path)) {
            mkdir($path, 0775);
            mkdir($js_path, 0775);
            mkdir($css_path, 0775);
            mkdir($models_path, 0775);
            mkdir($views_path, 0775);
            mkdir($controllers_path, 0775);
            mkdir($img_path, 0775);

            $rest_api_stub = <<<PHP

    '/!/{$slug}/(?P<api>[a-zA-Z\_-]+)/?(\?.*)?'=>'{$slug}\controller\API'
  
PHP;
            if (!$add_rest_api) $rest_api_stub = '';

            $php_api_stub = <<<PHP

    '{$class_slug}API.php'
  
PHP;
            if (!$add_php_api) $php_api_stub = '';
            $php_extension = <<<PHP
<?php

namespace {$slug};

/**
 * Implements hook_permission()
 */
function permission() {
    return array(
        'administer_{$slug}',
        'access_{$slug}'
    );
}

/**
 * Implements hook_menu()
 */
function menu() {
    return array();
}

/**
 * Implements hook_url()
 */
function url() {
    return array($rest_api_stub);
}

/**
 * Implements hook_libraries()
 */
function libraries() {
    return array($php_api_stub);
}

/**
 * Implements hook_cron()
 */
function cron() {
    // execute actions to be performed on cron
}

/**
 * Implements hook_twig_function()
 */
function twig_function() {
    // return an array of key value pairs.
    // key: twig_function_name
    // value: actual_function_name
    // You may use object functions as well
    // e.g. ObjectClass::actual_function_name
    return array();
}

/**
 * Implements hook_preprocess_page()
 */
function preprocess_page() {
    // execute actions just before the page is rendered.
}

/**
 * Implements hook_preprocess_boot()
 */
function preprocess_boot() {
    // execute actions after the core has been loaded but before the extensions have been loaded.
}

/**
 * Implements hook_postprocess_boot()
 */
function postprocess_boot() {
    // execute actions after core and extensions have been loaded.
}
PHP;
            $core_version = explode('.', Atomar::version());
            $core_version = $core_version[0] . '.x';
            $dependencies_stub = '';
            if (count($dependencies)) {
                $dependencies_stub = <<<PHP
,
  "dependencies": [
PHP;
                foreach ($dependencies as $dependency_slug) {
                    $dependencies_stub .= <<<PHP

    "$dependency_slug",
PHP;
                }
                $dependencies_stub = rtrim($dependencies_stub, ',');
                $dependencies_stub .= <<<PHP

  ]
PHP;
            }
            $php_info = <<<PHP
{
  "name": "{$name}",
  "description": "$description",
  "version": "1.0",
  "core": "{$core_version}"$dependencies_stub
}
PHP;
            $php_install = <<<PHP
<?php

namespace {$slug};

/**
 * Implements hook_uninstall()
 */
function uninstall() {
    // destroy tables and variables
    \$sql = <<<SQL
-- TODO: implement db un-installation
SQL;

    // perform un-installation
    \R::begin();
    try {
        \R::exec(\$sql);
        \R::commit();
        return true;
    } catch (\Exception \$e) {
        \R::rollback();
        log_error('Failed to un-install $name', \$e->getMessage());
        return false;
    }
}

/**
 * Implements hook_update_version()
 */
function update_1() {
    // prepare sql
    \$sql = <<<SQL
-- TODO: implement db installation
SQL;

    // perform installation
    \R::begin();
    try {
        \R::exec(\$sql);
        \R::commit();
        return true;
    } catch (\Exception \$e) {
        \R::rollback();
        log_error('Installation of $name failed', \$e->getMessage());
        return false;
    }
}
PHP;
            $php_api = <<<PHP
<?php

namespace {$slug};

/**
* This is the internal api class that can be used by third party extensions
*/
class {$class_slug}API
{
    public function stub() {
        // TODO: Implement stub() method.
    }
}
PHP;
            $md_doc = <<<PHP
$name
========

Documentation stub. Place some helpful information here to assist other developers in using or extending your extension.
PHP;
            $php_controller_api = <<<PHP
<?php

namespace {$slug}\controller;

use atomar\core\ApiController;

/**
 * This class provides json API interface.
 * NOTE: get_* and post_* methods must be public.
 *
 */
class API extends ApiController {

    function __construct() {
        parent::__construct();
        \$this->set_api_version('1.0.0');
    }

    /**
    * Perform actions before processing GET requests
    * @param array \$matches
    */
    protected function setup_get(\$matches=array()) {
        // TODO: Implement setup_get() method.
    }

    /**
    * Perform actions before processing POST requests
    * @param array \$matches
    */
    protected function setup_post(\$matches=array()) {
        // TODO: Implement setup_post() method.
    }

    /**
    * Example GET api method "test"
    */
    public function get_test() {
        return 'hello world!';
    }

    /**
    * Example POST api method "test"
    */
    public function post_test() {
        return 'hello world!';
    }
}
PHP;
            file_put_contents($path . 'hooks.php', $php_extension);
            @chmod($path . 'hooks.php', 0775);
            file_put_contents($path . 'manifest.json', $php_info);
            @chmod($path . 'manifest.json', 0775);
            file_put_contents($path . 'install.php', $php_install);
            @chmod($path . 'install.php', 0775);
            file_put_contents($path . 'README.md', $md_doc);
            @chmod($path . 'README.md', 0775);
            if ($add_php_api) {
                file_put_contents($path . $class_slug . 'API.php', $php_api);
                @chmod($path . $class_slug . 'API.php', 0775);
            }
            if ($add_rest_api) {
                file_put_contents($controllers_path . 'API.php', $php_controller_api);
                @chmod($controllers_path . 'API.php', 0775);
            }

            set_success('A new extension stub has been generated.');
        } else {
            set_error('An extension by that name already exists!');
        }
        $this->redirect('/admin/extensions');
    }

    /**
     * This method will be called before GET, POST, and PUT when the lightbox is returned to e.g. when using lightbox.dismiss_url or lightbox.return_url
     */
    function RETURNED() {

    }
}