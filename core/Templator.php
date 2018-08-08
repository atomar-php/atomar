<?php

namespace atomar\core;

use atomar\Atomar;
use atomar\hook\Menu;
use atomar\hook\Page;
use atomar\hook\Twig;

/**
 * Class View handles all of the view management
 * @package atomar
 */
class Templator {
    /**
     * An array of scripts that will be included on each page.
     * This allows for dynamic use of scripts so we don't have to load
     * everything on each page.
     * @deprecated we will no longer directly support injecting js and css
     * @var array
     */
    public static $js = array();

    /**
     * An array of scripts that will be executed after the page is ready.
     * This allows for dynamic use of scripts so we don't have to load
     * everything on each page.
     * @deprecated we will no longer directly support injecting js and css
     * @var array
     */
    public static $js_onload = array();

    /**
     * An array of css files to be inserted in the head of each page.
     * This allows for dynamic use of css so we don't have to load
     * everything on each page.
     * @deprecated we will no longer directly support injecting js and css
     * @var array
     */
    public static $css = array();

    /**
     * An array of css properties to be inserted in the head of each page.
     * This allows for dynamic use of css so we don't have to load
     * everything on each page.
     * @deprecated we will no longer directly support injecting js and css
     * @var array
     */
    public static $css_inline = array();

    /**
     * Puts jQuery into no conflict mode so it can work with prototype.
     * http://learn.jquery.com/using-jquery-core/avoid-conflicts-other-libraries/
     * @deprecated we will no longer directly support injecting js and css
     * @var boolean
     */
    public static $jquery_no_conflict = false;

    /**
     * Indicates that the Templator has been initialized by init()
     * @deprecated this no longer serves a purpose
     * @var bool
     */
    private static $is_initialized = false;

    /**
     * Initializes the view manager
     */
    public static function init() {
        // set up scripts. Order does matter.
        self::$js = array();

        // set up css. Order does matter
        self::$css = array();

        self::$is_initialized = true;
    }

    /**
     * Formats a relative path to an extension asset into an absolute path
     * @param $asset
     * @return string
     * @deprecated extension assets are now correctly handled by the AssetManager
     */
    public static function resolve_ext_asset($asset) {
        return '/' . ltrim(rtrim(Atomar::extension_dir(), '\\/'), '\\/') . '/' . $asset;
    }

    /**
     * Formats a relative path to an application asset into an absolute path
     * @param $asset
     * @return string
     * @deprecated application assets are now correctly handled by the AssetManager
     */
    public static function resolve_app_asset($asset) {
        return '/' . ltrim(rtrim(Atomar::application_dir(), '\\/'), '\\/') . '/' . $asset;
    }

    /**
     * Returns the template rendering engine
     * @return AtomarTwigEnvironment
     */
    private static function getTemplateEngine() {
        $loader = new \Twig_Loader_Filesystem();
        $loader->addPath(getcwd()); // TRICKY: we set a path in the _main namespace so template error messages make more sense.
        $loader->addPath(Atomar::application_dir(), Atomar::application_namespace());
        $extensions = \R::find('extension', 'is_enabled=1 and slug<>?', array(Atomar::application_namespace()));
        foreach($extensions as $ext) {
            $loader->addPath(Atomar::extension_dir() . $ext->slug, $ext->slug);
        }
        $loader->addPath(Atomar::atomar_dir(), Atomar::atomar_namespace());
        if (Atomar::debug()) {
            $twig = new AtomarTwigEnvironment($loader, array(
                'debug' => Atomar::debug(),
            ));
            $twig->addExtension(new \Twig_Extension_Debug());
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
        return $twig;
    }

    /**
     * Returns the properly formatted template path.
     * @param $template
     * @return string
     */
    private static function normalizeTemplatePath($template) {
        $template = ltrim($template, '/');
        if(strlen($template) == 0 || substr($template, 0, 1) != '@') {
            throw new \Exception('Templates must be namespaced. Try using \'@' . $template . '\'');
        }
        return $template;
    }

    /**
     * Renders the debug page
     * @param \Exception $e the exception that will be displayed
     * @return string html
     */
    public static function renderDebug($e) {
        $version = phpversion();
        return self::render_template('@atomar/views/debug.html', array(
            'e' => $e,
            'body' => print_r($e, true),
            'php_version' => $version
        ));
    }

    /**
     * Renders a template with injected arguments.
     * @param string $template the template that will be rendered
     * @param array $args the arguments to be injected
     * @return string html
     */
    public static function render_template($template, $args = array()) {
        $twig = self::getTemplateEngine();
        $template = self::normalizeTemplatePath($template);
        return $twig->render($template, $args);
    }

    /**
     * Renders an error page
     * @param string $title the title of the error page
     * @param string $message the message to display on the error page
     * @return string
     */
    public static function render_error($title, $message) {
        $loader = new \Twig_Loader_Filesystem(Atomar::atomar_dir() . '/views');
        $twig = new AtomarTwigEnvironment($loader);
        $atomar['version'] = Atomar::version();

        return $twig->render('error.html', array(
            'title' => $title,
            'message' => $message,
            'atomar' => $atomar
        ));
    }

    /**
     * Renders a view.
     * This calls a number of hooks to allow pre-processing the output
     *
     * @param string $template the path to the template that will be rendered
     * @param array $args the arguments that will be passed to the template
     * @param array $options optional rules regarding how the template is rendered.
     * @throws \Exception
     * @return string html
     */
    public static function renderView($template, $args = array(), $options = array()) {
        $twig = self::getTemplateEngine();

        $default_options = array(
            'render_messages' => true,
            'render_menus' => true,
            'trigger_preprocess_page' => true,
            'trigger_twig_function' => true,
            'trigger_menu' => true
        );
        $options = array_merge($default_options, $options);

        if ($options['trigger_twig_function']) {
            Atomar::hook(new Twig($twig));
        }

        $variables = array();
        if ($options['trigger_preprocess_page']) {
            $variables = Atomar::hook(new Page());
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
        $user['is_admin'] = Auth::has_authentication('administer_site');
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
        $args['atomar']['page_url'] = Router::page_url();
        $args['atomar']['email']['contact_email'] = Atomar::$config['email']['contact_email'];
        $args['atomar']['cron_token'] = Atomar::$config['cron_token'];
        $args['atomar']['maintenance'] = Atomar::get_system('maintenance_mode', '0');
        $args['atomar']['version'] = Atomar::version();
        $args['atomar']['year'] = date('Y');

        if ($options['render_menus']) {
            if ($options['trigger_menu']) Atomar::hook(new Menu());

            // render menu
            foreach (Atomar::$menu as $key => $menu) {
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
        $args['atomar']['debug'] = Atomar::debug();
        $args['atomar']['time'] = time();
        $args['atomar']['template']['name'] = $template;
        $args['atomar']['template']['variables'] = $variables;

        // load scripts
        if (count(self::$js)) {
            $args['atomar']['js'] = '\'' . implode('?v=' . Atomar::version() . '\',\'', self::$js) . '?v=' . Atomar::version() . '\'';
        }
        $args['atomar']['js_onload'] = implode(' ', self::$js_onload);
        $args['atomar']['jquery_no_conflict'] = self::$jquery_no_conflict;

        // load css
        $args['atomar']['css'] = self::$css;
        $args['atomar']['css_inline'] = implode(' ', self::$css_inline);

        // TRICKY: backwards compatibility
        $args['sys'] = $args['atomar'];

        $template = self::normalizeTemplatePath($template);
        return $twig->render($template, $args);
    }
}