<?php

namespace atomar\core;

use atomar\Atomar;
use atomar\hook\Menu;
use atomar\hook\PreprocessPage;
use atomar\hook\TwigFunction;

/**
 * Class View handles all of the view management
 * @package atomar
 */
class Templator {
    /**
     * An array of scripts that will be included on each page.
     * This allows for dynamic use of scripts so we don't have to load
     * everything on each page.
     * @var array
     */
    public static $js = array();

    /**
     * An array of scripts that will be executed after the page is ready.
     * This allows for dynamic use of scripts so we don't have to load
     * everything on each page.
     * @var array
     */
    public static $js_onload = array();

    /**
     * An array of css files to be inserted in the head of each page.
     * This allows for dynamic use of css so we don't have to load
     * everything on each page.
     * @var array
     */
    public static $css = array();

    /**
     * An array of css properties to be inserted in the head of each page.
     * This allows for dynamic use of css so we don't have to load
     * everything on each page.
     * @var array
     */
    public static $css_inline = array();

    /**
     * Puts jQuery into no conflict mode so it can work with prototype.
     * http://learn.jquery.com/using-jquery-core/avoid-conflicts-other-libraries/
     * @var boolean
     */
    public static $jquery_no_conflict = false;

    /**
     * An array of variables stored as key=>values to be include in the template.
     * This allows variables to be dynamically added to a template from anywhere within the system.
     * @var array
     */
    public static $global_template_vars = array();

    /**
     * Indicates that the Templator has been initialized by init()
     * @var bool
     */
    private static $is_initialized = false;

    /**
     * Initializes the view manager
     */
    public static function init() {
        // set up scripts. Order does matter.
        self::$js = array(
            '/assets/js/jquery.min.js',
            '/assets/js/bootstrap.min.js',
            '/assets/js/bootstrap.file-input.js',
            '/assets/js/bootstrap-datetimepicker.js',
            '/assets/js/chosen.jquery.min.js',
            '/assets/js/Validate.js',
            '/assets/js/Process.js',
            '/assets/js/Confirmation.js',
            '/assets/js/InlineEdit.js',
            '/assets/js/Lightbox.js',
            '/assets/js/sonic.js',
            '/assets/js/js_loader_animation.js',
            '/assets/js/functions.js',
            '/assets/js/main.js',
        );

        // set up css. Order does matter
        self::$css = array(
            '/assets/css/bootstrap.min.css',
            '/assets/css/chosen.min.css',
            '/assets/css/chosen-bootstrap.css',
            '/assets/css/bootstrap-datetimepicker.min.css',
            '/assets/css/main.css',
        );

        self::$is_initialized = true;
    }

    /**
     * Renders an error page
     * @param string $title the title of the error page
     * @param string $message the message to display on the error page
     * @return string
     */
    public static function render_error($title, $message) {
        \Twig_Autoloader::register();
        $loader = new \Twig_Loader_Filesystem(Atomar::atomar_dir() . '/views');
        $twig = new AtomarTwigEnvironment($loader, array(
            'debug' => true
        ));
        $twig->addExtension(new \Twig_Extension_Debug());
        $atomar['css'][] = '/assets/css/bootstrap.min.css';
        $atomar['css'][] = '/assets/css/main.css';
        $atomar['version'] = Atomar::version();

        $atomar['css_inline'] = <<<CSS
body {
  padding-top: 40px;
  padding-bottom: 40px;
  background-color: #eee;
}
.error-page {
  max-width: 330px;
  padding: 15px;
  margin: 0 auto;
}
.error-page .error-page-heading, .error-page .checkbox {
  margin-bottom: 10px;
}
.error-page .form-control {
  position: relative;
  font-size: 16px;
  height: auto;
  padding: 10px;
  -webkit-box-sizing: border-box;
  -moz-box-sizing: border-box;
  box-sizing: border-box;
}
.error-page #field-username {
  margin-bottom: -1px;
  border-radius: 0;
}
.error-page #field-email {
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  margin-bottom: -1px;
}
.error-page #field-password {
  margin-bottom: 10px;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
}
.error-page .checkbox {
  font-weight: normal;
}
CSS;
        return $twig->render('error.html', array(
            'body_class' => 'install',
            'title' => $title,
            'message' => $message,
            'atomar' => $atomar,
            'sys' => $atomar // TRICKY: backwards compatability
        ));
    }

    /**
     * Formats a relative path to an extension asset into an absolute path
     * @param $asset
     * @return string
     */
    public static function resolve_ext_asset($asset) {
        return '/' . ltrim(rtrim(Atomar::extension_dir(), '\\/'), '\\/') . '/' . $asset;
    }

    /**
     * Formats a relative path to an application asset into an absolute path
     * @param $asset
     * @return string
     */
    public static function resolve_app_asset($asset) {
        return '/' . ltrim(rtrim(Atomar::application_dir(), '\\/'), '\\/') . '/' . $asset;
    }

    /**
     * Utility to render the view
     *
     * @param string $template the path to the template that will be rendered
     * @param array $args the arguments that will be passed to the template
     * @param array $options optional rules regarding how the template is rendered.
     * @throws \Exception
     * @return string
     */
    public static function render_view($template, $args = array(), $options = array()) {
        $default_options = array(
            'render_messages' => true,
            'render_menus' => true,
            'trigger_preprocess_page' => true,
            'trigger_twig_function' => true,
            'trigger_menu' => true
        );
        $options = array_merge($default_options, $options);
        if ($options['trigger_preprocess_page']) Atomar::hook(new PreprocessPage());
        try {
            // initialize twig template engine
            $loader = new \Twig_Loader_Filesystem(array(
                Atomar::atomar_dir() . DIRECTORY_SEPARATOR . 'views',
                Atomar::extension_dir(),
                Atomar::application_dir() . '../'
            ));
            if (Atomar::$config['debug']) {
                $twig = new AtomarTwigEnvironment($loader, array(
                    'debug' => Atomar::$config['debug'],
                ));
                require_once(Atomar::atomar_dir() . '/vendor/Twig/Extension/Debug.php');
                $twig->addExtension(new \Twig_Extension_debug());
                // delete the cache if it exists
                if (is_dir(Atomar::$config['cache'] . 'twig')) {
                    deleteDir(Atomar::$config['cache'] . 'twig');
                }
            } else {
                if (!is_dir(Atomar::$config['cache'] . 'twig')) {
                    $old = umask(0002);
                    mkdir(Atomar::$config['cache'] . 'twig', 0777, true);
                    umask($old);
                }
                $twig = new AtomarTwigEnvironment($loader, array(
                    'cache' => Atomar::$config['cache'] . 'twig'
                ));
            }

            // define template utilities
            $multi_select = new \Twig_SimpleFunction('multi_select', function ($options, $selected = array(), $key_field = 'key', $value_field = 'value', $show_blank_option_first = '1') {
                $fields = array(
                    'key' => $key_field,
                    'value' => $value_field
                );

                $result = $show_blank_option_first ? '<option></option>' : '';
                foreach ($options as $option) {
                    $is_selected = '';
                    if (in_array($option[$fields['key']], $selected)) {
                        $is_selected = 'selected';
                    }
                    $result .= '<option value="' . $option[$fields['key']] . '" ' . $is_selected . '>' . $option[$fields['value']] . '</option>';
                }
                echo $result;
            });
            $single_select = new \Twig_simpleFunction('single_select', function ($options, $selected = null, $key_field = 'key', $value_field = 'value') {
                $fields = array(
                    'key' => $key_field,
                    'value' => $value_field
                );
                $last_group = null;
                $result = '<option></option>';
                foreach ($options as $option) {
                    $is_selected = '';
                    if ($selected != null && $option[$fields['key']] === $selected) {
                        $is_selected = 'selected';
                    }
                    // generate groups
                    if (isset($option['group'])) {
                        if ($last_group !== $option['group']) {
                            // close last group
                            if ($last_group !== null) {
                                $result .= '</optgroup>';
                            }
                            // open new group
                            $result .= '<optgroup label="' . $option['group'] . '">';
                            $last_group = $option['group'];
                        }
                    }
                    // generate options
                    $result .= '<option value="' . $option[$fields['key']] . '" ' . $is_selected . '>' . $option[$fields['value']] . '</option>';
                }
                echo $result;
            });
            $fancy_date = new \Twig_simpleFunction('fancy_date', function ($date, $allow_empty = false) {
                if ($date == '') {
                    echo fancy_date(time(), $allow_empty);
                } else {
                    echo fancy_date(strtotime($date), $allow_empty);
                }
            });
            $compact_date = new \Twig_simpleFunction('compact_date', function ($date) {
                if ($date == '') {
                    echo compact_date();
                } else {
                    echo compact_date(strtotime($date));
                }
            });
            $sectotime = new \Twig_simpleFunction('sectotime', function ($time) {
                echo sectotime($time);
            });
            $simple_date = new \Twig_simpleFunction('simple_date', function ($date) {
                if ($date == '') {
                    echo simple_date();
                } else {
                    echo simple_date(strtotime($date));
                }
            });
            $word_trim = new \Twig_simpleFunction('word_trim', 'word_trim');
            $letter_trim = new \Twig_simpleFunction('letter_trim', 'letter_trim');
            $print_debug = new \Twig_simpleFunction('print_debug', 'print_debug');
            $twig->addFunction($multi_select);
            $twig->addFunction($single_select);
            $twig->addFunction($fancy_date);
            $twig->addFunction($compact_date);
            $twig->addFunction($sectotime);
            $twig->addFunction($simple_date);
            $twig->addFunction($word_trim);
            $twig->addFunction($letter_trim);
            $twig->addFunction($print_debug);
            if ($options['trigger_twig_function']) {
                Atomar::hook(new TwigFunction($twig));
            }

            // prepare user
            if (Auth::$user) {
                $user = Auth::$user->export();
                $user['authenticated'] = 1;
            } else {
                $user = array();
                $user['authenticated'] = 0;
            }
            if (isset($user['last_login'])) {
                $user['last_login'] = fancy_date(strtotime($user['last_login']));
            } else {
                $user['last_login'] = '';
            }
            $user['is_admin'] = Auth::is_admin();
            $user['is_super'] = Auth::is_super();
            $args['atomar']['user'] = $user;
            unset($user);

            // prepare return
            if (isset($_SESSION['return']) && !isset($args['return'])) {
                $args['return'] = $_SESSION['return'];
            }

            // prepare site info
            $args['atomar']['favicon'] = Atomar::$config['favicon'];
            $args['atomar']['site_name'] = Atomar::$config['site_name'];
            $args['atomar']['site_url'] = Atomar::$config['site_url'];
            $args['atomar']['email']['contact_email'] = Atomar::$config['email']['contact_email'];
            $args['atomar']['cron_token'] = Atomar::$config['cron_token'];
            $args['atomar']['maintenance'] = system_get('maintenance_mode', '0');
            $args['atomar']['version'] = Atomar::version();
            $args['atomar']['year'] = date('Y');

            if ($options['render_menus']) {
                // prepare menu
                Atomar::$menu['primary_menu']['/'] = array(
                    'link' => l('Home', '/'),
                    'class' => array(),
                    'weight' => -9999,
                    'access' => array(),
                    'menu' => array()
                );
                // admin users
                if (Auth::has_authentication('administer_site')) {
                    Atomar::$menu['primary_menu']['/admin'] = array(
                        'link' => l('<span class="glyphicon glyphicon-cog"></span>', '/admin'),
                        'class' => array(),
                        'weight' => 9999,
                        'access' => 'administer_site',
                        'menu' => array()
                    );
                }
                if (Auth::$user) {
                    // authenticated users
                    Atomar::$menu['primary_menu']['/user/submenu'] = array(
                        'link' => l('<span class="glyphicon glyphicon-user"></span> <b class="caret"></b>', '#', array(
                            'dropdown-toggle'
                        ), array(
                            'data-toggle' => 'dropdown'
                        )),
                        'class' => array(
                            'dropdown'
                        ),
                        'weight' => 8888,
                        'access' => array(),
                        'menu' => array(
                            'class' => array(
                                'dropdown-menu',
                                'pull-right'
                            ),
                            '/user/#' => array(
                                'value' => Auth::$user->first_name . ' ' . Auth::$user->last_name . ' (' . Auth::$user->role->slug . ')',
                                'class' => array('section-header'),
                                'weight' => -9999,
                                'access' => array(),
                                'menu' => array()
                            ),
                            '/user/' . Auth::$user->id => array(
                                'link' => l('Manage Account', '/user/' . Auth::$user->id),
                                'class' => array(),
                                'weight' => 0,
                                'access' => array(),
                                'menu' => array()
                            ),
                            '/user/logout' => array(
                                'link' => l('Logout', '/user/logout'),
                                'class' => array(),
                                'weight' => 9999,
                                'access' => array(),
                                'menu' => array()
                            ),
                        )
                    );
                } else {
                    Atomar::$menu['primary_menu']['/user/login'] = array(
                        'link' => l('login', '/user/login'),
                        'class' => array(),
                        'weight' => 0,
                        'access' => array(),
                        'menu' => array()
                    );
                }

                // secondary menu
                Atomar::$menu['secondary_menu']['admin_menu'] = array(
                    'title' => 'Admin Menu',
                    'class' => array('section-header'),
                    'weight' => -9999,
                    'access' => array(),
                    'menu' => array()
                );
                Atomar::$menu['secondary_menu']['/admin'] = array(
                    'link' => l('Admin home', '/admin'),
                    'options' => array(
                        'active' => 'exact'
                    ),
                    'class' => array(),
                    'weight' => -8888,
                    'access' => 'administer_site',
                    'menu' => array()
                );
                Atomar::$menu['secondary_menu']['/admin/users'] = array(
                    'link' => l('Users', '/admin/users/'),
                    'class' => array(),
                    'weight' => 500,
                    'access' => 'administer_users',
                    'menu' => array(),
                );
                Atomar::$menu['secondary_menu']['/admin/roles'] = array(
                    'link' => l('Roles', '/admin/roles/'),
                    'class' => array(),
                    'weight' => 600,
                    'access' => 'administer_roles',
                    'menu' => array()
                );
                Atomar::$menu['secondary_menu']['/admin/permissions'] = array(
                    'link' => l('Permissions', '/admin/permissions/'),
                    'class' => array(),
                    'weight' => 700,
                    'access' => 'administer_permissions',
                    'menu' => array()
                );
                Atomar::$menu['secondary_menu']['/admin/settings'] = array(
                    'link' => l('Settings', '/admin/settings/'),
                    'class' => array(),
                    'weight' => 800,
                    'access' => 'administer_settings',
                    'menu' => array()
                );
                Atomar::$menu['secondary_menu']['/admin/performance'] = array(
                    'link' => l('Performance', '/admin/performance/'),
                    'class' => array(),
                    'weight' => 850,
                    'access' => 'administer_performance',
                    'menu' => array()
                );
                Atomar::$menu['secondary_menu']['/admin/extensions'] = array(
                    'link' => l('Extensions', '/admin/extensions/'),
                    'class' => array(),
                    'weight' => 900,
                    'access' => 'administer_extensions',
                    'menu' => array()
                );

                // TRICKY: allows \atomar\controllers\CAdminDocumentation.php to override the docs menu so we don't generate the menu unnecessarily
                $docs_menu = Atomar::$menu['secondary_menu']['/admin/documentation']['menu'];
                Atomar::$menu['secondary_menu']['/admin/documentation'] = array(
                    'link' => l('Documentation', '/admin/documentation/'),
                    'class' => array(),
                    'weight' => 9999,
                    'access' => 'view_system_documentation',
                    'menu' => array(
                        'options' => array('visible' => 'when_active')
                    )
                );
                if (is_array($docs_menu)) {
                    Atomar::$menu['secondary_menu']['/admin/documentation']['menu'] = array_merge(Atomar::$menu['secondary_menu']['/admin/documentation']['menu'], $docs_menu);
                }

                if ($options['trigger_menu']) Atomar::hook(new Menu());


                // render menu
                foreach (Atomar::$menu as $key => $menu) {
                    // TODO: sort menu.
                    $args['atomar']['menu'][$key] = render_menu($menu, false, $key);
                }
            }

            if ($options['render_messages'] && isset($_SESSION['messages'])) {
                // display messages
                foreach ($_SESSION['messages'] as $type => $messages) {
                    $args['atomar'][$type] = $messages;
                    $_SESSION['messages'][$type] = array();
                }
            }

            // load other system variables
            $args['atomar']['debug'] = Atomar::$config['debug'];
            $args['atomar']['time'] = time();
            $args['atomar']['template']['name'] = $template;
            $args['atomar']['template']['variables'] = self::$global_template_vars;

            // load scripts
            if (count(self::$js)) {
                $args['atomar']['js'] = '\'' . implode('?v=' . Atomar::version() . '\',\'', self::$js) . '?v=' . Atomar::version() . '\'';
            }
            $args['atomar']['js_onload'] = implode(' ', self::$js_onload);
            $args['atomar']['jquery_no_conflict'] = self::$jquery_no_conflict;

            // load css
            $args['atomar']['css'] = self::$css;
            $args['atomar']['css_inline'] = implode(' ', self::$css_inline);

            // TRICKY: backwards compatability
            $args['sys'] = $args['atomar'];

            return $twig->render($template, $args);
        } catch (\Exception $ex) {
            if ($options['_controller']['type'] == 'lightbox') {
                // Let lightboxes handle their own exceptions
                throw $ex;
            } elseif (Atomar::$config['debug']) {
                // print the error
                print_debug('An exception was encountered while rendering the view');
                print_debug($ex);
            } else {
                // fall back is to display 500 error.
                $loader = new \Twig_Loader_Filesystem(Atomar::atomar_dir() . '/views');
                $twig = new AtomarTwigEnvironment($loader);
                Logger::log_error($ex->getMessage(), $ex->getTraceAsString());
                return $twig->render('500.html');
            }
        }
    }
}