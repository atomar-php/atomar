<?php
use atomar\core\Auth;
use atomar\core\Logger;
use atomar\core\ModelException;
use atomar\core\Router;

/**
 * Efficiently prepends a string to a file
 * @param string $path the file to which the contents will be prepended
 * @param string $contents the contents to prepend
 */
function file_prepend_contents($path, $contents) {
    $context = stream_context_create();
    $fp = fopen($path, 'r', 1, $context);
    $tmpname = md5($contents . time());
    file_put_contents($tmpname, $contents);
    file_put_contents($tmpname, $fp, FILE_APPEND);
    fclose($fp);
    unlink($path);
    rename($tmpname, $path);
}

/**
 * Encodes some data as a json object and echos it to the client. This method exits the php script.
 * NOTE: this method name is a little missleading because it renders and echos the response
 * instead of just rendering like the other rendering methods.
 * @param mixed $data the data that will be rendered as json.
 */
function render_json($data) {
    header('Content-Type: application/json');
    $json = json_encode($data);
    if ($json) {
        echo $json;
        exit;
    } else {
        Logger::log_warning('render_json: Failed to encode as JSON', $data);
        echo json_encode(array(
            'status' => 'error',
            'message' => 'an error occured while encoding the response as json'
        ));
        exit;
    }
}

// http://stackoverflow.com/questions/2916232/call-to-undefined-function-apache-request-headers
if (!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        static $arh = array();
        if (!$arh) {
            $rx_http = '/\AHTTP_/';
            foreach ($_SERVER as $key => $val) {
                if (preg_match($rx_http, $key)) {
                    $arh_key = preg_replace($rx_http, '', $key);
                    $rx_matches = array();
                    // do some nasty string manipulations to restore the original letter case
                    // this should work in most cases
                    $rx_matches = explode('_', $arh_key);
                    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                        foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
        }
        return ($arh);
    }
}

/**
 * Create a safe version of symlink that supports windows on older versions of php.
 * This method doesn't actually work on windows, but at least it provides the stub so
 * we don't encounter an exception.
 *
 */
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && vercmp(phpversion(), '5.4.0') === -1) {
    function safe_symlink($target, $link) {
        if (substr($link, -4) !== '.lnk') $link .= '.lnk';
        if (!file_exists($link)) {
            $shell = new COM('WScript.Shell');
            $shortcut = $shell->createshortcut($link);
            $shortcut->targetpath = $target;
            $shortcut->save();
        }
    }
} else {
    function safe_symlink($target, $link) {
        if (!file_exists($link)) {
            if (is_string($target) && is_string($link)) {
                symlink($target, $link);
            } else {
                Logger::log_error("invalid symlink arguments", array(
                    'target' => $target,
                    'link' => $link
                ));
            }
        }
    }
}

/**
 * Streams a file while supporting HTTP_RANGE. This allows downloads to be paused
 * and enables seeking in audio files.
 * The source was taken from
 * http://stackoverflow.com/questions/157318/resumable-downloads-when-using-php-to-send-the-file
 *
 * @param string $file the path to the file that will be streamed
 * @param array $options additional options to customize how the file is streamed: content_type, name
 */
function stream_file($file, $options = array()) {
    $defaults = array(
        'content_type' => 'application/octet-stream'
    );
    $options = array_merge($defaults, $options);

    // Avoid sending unexpected errors to the client - we should be serving a file,
    // we don't want to corrupt the data we send
    @error_reporting(0);

    // Make sure the files exists, otherwise we are wasting our time
    if (!file_exists($file)) {
        header("HTTP/1.1 404 Not Found");
        exit;
    }

    // Get the 'Range' header if one was sent
    if (isset($_SERVER['HTTP_RANGE'])) $range = $_SERVER['HTTP_RANGE']; // IIS/Some Apache versions
    else if ($apache = apache_request_headers()) { // Try Apache again
        $headers = array();
        foreach ($apache as $header => $val) $headers[strtolower($header)] = $val;
        if (isset($headers['range'])) $range = $headers['range']; else $range = FALSE; // We can't get the header/there isn't one set
    } else $range = FALSE; // We can't get the header/there isn't one set

    // Get the data range requested (if any)
    $filesize = filesize($file);
    if ($range) {
        $partial = true;
        list($param, $range) = explode('=', $range);
        if (strtolower(trim($param)) != 'bytes') { // Bad request - range unit is not 'bytes'
            header("HTTP/1.1 400 Invalid Request");
            exit;
        }
        $range = explode(',', $range);
        $range = explode('-', $range[0]); // We only deal with the first requested range
        if (count($range) != 2) { // Bad request - 'bytes' parameter is not valid
            header("HTTP/1.1 400 Invalid Request");
            exit;
        }
        if ($range[0] === '') { // First number missing, return last $range[1] bytes
            $end = $filesize - 1;
            $start = $end - intval($range[0]);
        } else if ($range[1] === '') { // Second number missing, return from byte $range[0] to end
            $start = intval($range[0]);
            $end = $filesize - 1;
        } else { // Both numbers present, return specific range
            $start = intval($range[0]);
            $end = intval($range[1]);
            if ($end >= $filesize || (!$start && (!$end || $end == ($filesize - 1)))) $partial = false; // Invalid range/whole file specified, return whole file
        }
        $length = $end - $start + 1;
    } else $partial = false; // No range requested

    // Send standard headers
    header('Content-Type: ' . $options['content_type']);
    header('Content-Length: ' . $filesize);
    if (isset($options['expires'])) {
        header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $options['expires']));
    }
    // force download
    if (isset($options['download']) && $options['download'] == true) {
        if (isset($options['name']) && $options['name'] != '') {
            header('Content-Disposition: attachment; filename="' . basename($options['name']) . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        }
    }
    header('Accept-Ranges: bytes');

    // if requested, send extra headers and part of file...
    if ($partial) {
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$filesize");
        if (!$fp = fopen($file, 'r')) { // Error out if we can't read the file
            header("HTTP/1.1 500 Internal Server Error");
            exit;
        }
        if ($start) fseek($fp, $start);
        while ($length) { // Read in blocks of 8KB so we don't chew up memory on the server
            $read = ($length > 8192) ? 8192 : $length;
            $length -= $read;
            print(fread($fp, $read));
        }
        fclose($fp);
    } else readfile($file); // ...otherwise just send the whole file

    // Exit here to avoid accidentally sending extra content on the end of the file
    exit;
}

/**
 * Check if two version strings of the format x.x.x are equal.
 *
 * @param string $ver1
 * @param string $ver2
 * @return int -1 if ver1 is less than ver2; 1 if ver1 is greater than ver2, and 0 if they are equal.
 */
function vercmp($ver1, $ver2) {
    $parts1 = explode('.', $ver1);
    $parts2 = explode('.', $ver2);

    // normalize version numbers. x or * are wild cards.
    if (count($parts1) > count($parts2)) {
        if (strtolower($parts2[count($parts2) - 1]) == 'x' || strtolower($parts2[count($parts2) - 1]) == '*') {
            $attachement = 'x';
        } else {
            $attachement = '0';
        }
        for ($i = count($parts2); $i < count($parts1); $i++) {
            $parts2[] = $attachement;
        }
    } else if (count($parts2) > count($parts1)) {
        if (strtolower($parts1[count($parts1) - 1]) == 'x' || strtolower($parts1[count($parts1) - 1]) == '*') {
            $attachement = 'x';
        } else {
            $attachement = '0';
        }
        for ($i = count($parts1); $i < count($parts2); $i++) {
            $parts1[] = $attachement;
        }
    }

    // compare components
    $result = 0;
    foreach ($parts1 as $i => $value) {
        if (strtolower($value) == 'x' || strtolower($parts2[$i]) == 'x') {
            continue;
        }
        if (($result = numcmp((float)$value, (float)$parts2[$i])) != 0) {
            break;
        }
    }
    return $result;
}

/**
 * Check if two numbers are equal
 *
 * @param string $num1 the first number
 * @param string $num2 the second number
 * @param int -1 if num1 is less than num2; 1 if num1 is greater than num2, and 0 if they are equal.
 * @return int
 */
function numcmp($num1, $num2) {
    if ($num1 > $num2) {
        return 1;
    } else if ($num1 == $num2) {
        return 0;
    } else {
        return -1;
    }
}


/**
 * This method allows you to easily insert parameters into a url
 *
 * @param string $url the url that will receive the parameter
 * @param string $key the new parameter key
 * @param string $value the new parameter value
 * @return string the newly parameterized url
 */
function parameterize_url($url, $key, $value) {
    $key = urlencode($key);
    $value = urlencode($value);
    $kvp = explode('&', $url);

    $i = count($kvp);
    while ($i--) {
        $x = explode('=', $kvp[$i]);
        if ($x[0] == $key) {
            $x[1] = $value;
            $kvp[$i] = implode('=', $x);
            break;
        }
    }
    if ($i < 0) {
        $kvp[count($kvp)] = $key . '=' . $value;
    }
    if (count(explode('?', $url)) == 1) {
        $new_url = implode('?', $kvp);
    } else {
        $new_url = implode('&', $kvp);
    }
    return $new_url;
}

/**
 * convert a number of seconds to time format hh:mm:ss
 *
 * @param int $s the seconds that will be converted
 * @return string the time quantity in hh:mm:ss format
 */
function sectotime($s) {
    $h = 0;
    $m = 0;
    $s = $s < 0 ? 0 : $s;
    // hours
    if ($s > 3600) {
        $h = floor($s / 3600.0);
        $s -= $h * 3600;
    }
    // minutes
    if ($s > 60) {
        $m = floor($s / 60.0);
        $s -= $m * 60;
    }
    $time = sprintf('%1$02d:%2$02d:%3$02d', $h, $m, $s);
    return $time;
}

/**
 * convert the time format 00:00:00 to seconds
 *
 * @param string $t the timeformat that will be converted to seconds
 * @return int the number of seconds derived from the input time.
 */
function timetosec($t) {
    list($hr, $min, $sec) = explode(':', $t);
    $sec = intval($sec) + intval($min) * 60 + intval($hr) * 60 * 60;
    return $sec;
}

// format time into mysql date time string
/**
 * formats unix time into database datetime format.
 * @param int $time a unix time stamp or null to use the current time.
 * @return string a database datetime string
 */
function db_date($time = null) {
    if ($time == null || trim($time) == '') {
        $time = time();
    }
    return date('Y-m-d H:i:s', $time);
}

// check if a table exists in the database

/**
 * Checks if a table exists in the database
 *
 * @param string $table the name of a database table
 * @return boolean
 */
function table_exists($table) {
    $sql = <<<SQL
SHOW TABLES LIKE :table
SQL;
    $results = R::getAll($sql, array(
        ':table' => $table
    ));
    return count($results) > 0;
}

/**
 * Format time for use on the website e.g. May 15th 2014 at 12:30 pm
 * @param int $time the unix time or null to use the current time.
 * @param bool $allow_empty if this is true and the entered time is empty the result will be 'never'
 * @return string the formatted datetime string
 */
function fancy_date($time = null, $allow_empty = false) {
    if ($time === null || $time == '') {
        if ($allow_empty) {
            return 'never';
        } else {
            $time = time();
        }
    }
    return date('M jS Y \a\t h:i A', $time);
}

/**
 * Formats unix time into a simple date format e.g. May 16th 2014
 * @param int $time the unix time or null to use the current time.
 * @return string the formatted time e.g. May 16th 2014
 */
function simple_date($time = null) {
    if ($time === null) {
        $time = time();
    }
    return date('M jS Y', $time);
}

/**
 * Formats unix time into a compact date format e.g. 5/16/14
 * @param int $time the unix time or null to use the current time.
 * @return string the formatted time e.g. 5/16/14
 */
function compact_date($time = null) {
    if ($time === null) {
        $time = time();
    }
    return date('m/d/y', $time);
}

/**
 * Trims a string by character and appends ellipsis if it was too long
 * @param string $string the string to trim
 * @param int $length the maximum length of the string (including ellipsis)
 * @return string the trimmed string.
 */
function letter_trim($string, $length) {
    if (strlen($string) > $length) {
        $length -= 3; // leave room for ellipsis
        return substr($string, 0, $length) . '...';
    } else {
        return $string;
    }
}

/**
 * Trims a phrase by word and appends ellipsis if it was too long
 * @param string $string the phrase to strim
 * @param int $max_width the maximum length of the phrase (including ellipsis)
 * @return string the trimmed phrase
 */
function word_trim($string, $max_width) {
    $max_width -= 3; // leave room for ellipsis
    $parts = preg_split('/([\s\n\r]+)/', $string, null, PREG_SPLIT_DELIM_CAPTURE);
    $parts_count = count($parts);
    $length = 0;
    $last_part = 0;
    for (; $last_part < $parts_count; ++$last_part) {
        $length += strlen($parts[$last_part]);
        if ($length > $max_width) {
            break;
        }
    }
    $result = implode(array_slice($parts, 0, $last_part));
    // add ellipsis
    if (strlen($result) < strlen($string)) {
        $result .= '...';
    }
    return $result;
}

/**
 * Calculate how many hours it will-be/has-been until/since the given time.
 * @param int $time the unix time
 * @param boolean $precise specifies if minutes and seconds should be included in the result or just rounded to hours.
 * @return string the formatted time period.
 */
function time_until_date($time, $precise = false) {
    $now = time();
    $time_is_past = $time < $now;
    $time = abs($time - $now);
    $string_time = '';
    if ($precise) {
        $h = floor(abs($time) / 60 / 60);
        $time -= $h * 60 * 60;
        $m = floor(abs($time) / 60);
        $time -= $m * 60;
        $s = floor(abs($time));
        if ($h > 0) {
            $string_time .= $h . ' hour';
            if ($h > 1) {
                $string_time .= 's';
            }
            if (($m > 0 || $s > 0) && !($m > 0 && $s > 0)) {
                $string_time .= ' and ';
            } else if ($m > 0 && $s > 0) {
                $string_time .= ' ';
            }
        }
        if ($m > 0) {
            $string_time .= $m . ' minute';
            if ($m > 1) {
                $string_time .= 's';
            }
            if ($s > 0) {
                $string_time .= ' and ';
            }
        }
        if ($s > 0) {
            $string_time .= $s . ' second';
            if ($s > 1) {
                $string_time .= 's';
            }
        }
    } else {
        $h = floor(abs($time) / 60 / 60);
        $string_time = $h . ' hour';
        if ($h > 1) {
            $string_time .= 's';
        }
    }
    if ($time_is_past) {
        return $string_time . ' ago';
    } else {
        return $string_time;
    }
}

/**
 * Checks if a string is a valid email
 * source http://www.linuxjournal.com/article/9585
 * @param string $email an email address
 * @return boolean
 */
function is_email($email) {
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email);
}

/**
 * Adds an error message that will be displayed the next time a page is rendered.
 * @param String $message the message to display
 */
function set_error($message) {
    $_SESSION['messages']['error'][] = $message;
}

/**
 * Adds a warning message that will be displayed the next time a page is rendered.
 * @param String $message the message to display
 */
function set_warning($message) {
    $_SESSION['messages']['warning'][] = $message;
}

/**
 * Adds a notice message that will be displayed the next time a page is rendered.
 * @param String $message the message to display
 */
function set_notice($message) {
    $_SESSION['messages']['notice'][] = $message;
}

/**
 * Adds a success message that will be displayed the next time a page is rendered.
 * @param String $message the message to display
 */
function set_success($message) {
    $_SESSION['messages']['success'][] = $message;
}

/**
 * Adds a debug message that will be displayed the next time a page is rendered.
 * @param $message the message to display
 */
function set_debug($message) {
    print_debug($message);
    $_SESSION['messages']['debug'][] = $message;
}

/**
 * Prints a variable in pretty format to the page
 * @param mixed $data the variable to debug
 * @param boolean $multiple will print the inputed value as if it were an array of seperate objects. otherwise it will print them all together.
 *
 */
function print_debug($data, $multiple = false) {
    echo '<pre style="background: #fff; color: #000; padding: 10px; border: solid 1px #ccc;">';
    if (is_array($data) && $multiple) {
        foreach ($data AS $i) {
            echo '<br/>---DEBUG---><br/>';
            print_r($i);
        }
    } else {
        print_r($data);
    }
    echo '</pre>';
}

/**
 * Performs a GET request on a url
 * @param string $url the url to get
 * @param int $timeout the length of time (seconds) before the request times out.
 * @return string the response from the get request
 *
 */
function get($url, $timeout = 5) {
    $ch = curl_init($url);
    // curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

/**
 * Performs a POST request on a url
 * @param string $url the url to get
 * @param array $vars the data that will be posted to the url in key=>value form.
 * @param int $timeout the length of time (seconds) before the request times out.
 * @return string the response from the post request
 */
function post($url, $vars, $timeout = 5) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    return $response;
}

/**
 * Utility method for destroying beans. This doesn't do anything special
 * right now. Eventually it will do some error handling.
 * @param RedBean $bean the bean to trash
 */
function trash($bean) {
    R::trash($bean);
}

/**
 * Utility method for storing beans and catching any model exceptions.
 * returns array of ids of input is array of beans. id will be zero if
 * an exception occured.
 * @param mixed $bean the bean or array of beans to save
 * @return int returns the id(s) of the bean(s) or 0.
 */
function store($bean) {
    if (is_array($bean)) {
        $ids = array();
        foreach ($bean as $b) {
            try {
                $ids[] = R::store($b);
            } catch (ModelException $e) {
                $ids[] = 0;
            }
        }
        return $ids;
    } else {
        try {
            return R::store($bean);
        } catch (ModelException $e) {
            return 0;
        }
    }
}

/**
 * Utility for removing a directory and any files in it
 * @param string $dir_path the path of the directory that will be deleted
 * @return boolean true of the delete was successful otherwise false.
 */
function deleteDir($dir_path) {
    // validate input
    if (!is_dir($dir_path)) {
        Logger::log_error('deleteDir: $dir_path must be a directory');
        return false;
    }

    // destroy links
    // TODO: this does not work
    // until this is working the two warning logs should be commented so we don't get useless log messages.
    if (is_link($dir_path)) {
        return rmdir($dir_path);
    }

    // handle everything else
    $dir_path = rtrim($dir_path, '/') . '/';
    $files = glob($dir_path . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            if ($file == '.' || $file == '..') continue;
            if (!deleteDir($file)) {
                // log_warning('Failed to delete '.$file, debug_backtrace());
                continue;
            }
        } else {
            if (!unlink($file)) {
                // log_warning('Failed to delete '.$file, debug_backtrace());
                continue;
            }
        }
    }
    return rmdir($dir_path);
}

/**
 * Recursive method to render the menu array. The best example of how to build the menu array is in bootstrap.php
 * @param array $menu the menu array to render.
 * @param boolean $active used interally for recursive calls
 * @param string $name the name of the menu. IF this is set an id will be created from it and added to the ul.
 * @return string the rendered menue
 */
function render_menu($menu, $active = false, $name = null) {
    // ul classes
    if (!isset($menu['class'])) $menu['class'] = array();
    $result = '<ul class="' . implode(' ', $menu['class']) . '"';
    unset($menu['class']);
    if (is_array($menu['attribute'])) {
        foreach ($menu['attribute'] as $key => $attribute) {
            $result .= $key . '="' . $attribute . '" ';
        }
    }
    if ($name != null) {
        $name = str_replace('_', '-', human_to_machine($name));
        $result .= 'id="' . $name . '" ';
    }
    $result .= '>';

    // menu options
    if (!isset($menu['options'])) $menu['options'] = array();
    foreach ($menu['options'] as $key => $value) {
        switch ($key) {
            case 'visible':
                if ($value == 'when_active' && !$active) {
                    return '';
                }
                break;

            default:
                # code...
                break;
        }
    }
    unset($menu['options']);

    // sort by weight
    foreach ($menu as $key => $value) {
        if (!isset($value['weight'])) {
            $value['weight'] = 0;
        }
        $sort_by_weight[$key] = $value['weight'];
    }
    array_multisort($sort_by_weight, SORT_ASC, $menu);

    // render
    foreach ($menu as $m) {
        if (!isset($m['class'])) $m['class'] = array();
        if (!isset($m['attribute'])) $m['attribute'] = array();

        // link options
        $exact = false;
        if (!isset($m['options'])) $m['options'] = array();
        foreach ($m['options'] as $key => $value) {
            switch ($key) {
                case 'active':
                    $exact = $value == 'exact';
                    break;

                default:
                    # code...
                    break;
            }
        }
        unset($m['options']);

        if (isset($m['menu']) && count($m['menu']) > 0) {
            // render li
            $li_classes = trim(@implode(' ', $m['class']) . (l_active($m['link']['url'], $exact) ? ' active' : '') . (l_active($m['link']['url'], true) ? ' current' : ''));
            if ($li_classes != '') {
                $result .= '<li class="' . $li_classes . '" ';
            } else {
                $result .= '<li ';
            }
            if (isset($m['attribute'])) {
                foreach ($m['attribute'] as $key => $attribute) {
                    $result .= $key . '="' . $attribute . '" ';
                }
            }
            $result .= '>';
            $result .= $m['text'];

            // render element
            if (isset($m['link'])) {
                if (Auth::has_authentication($m['access'])) {
                    // render as link
                    $result .= '<a href="' . $m['link']['url'] . '" class="' . @implode(' ', $m['link']['class']) . '" ';
                    if (isset($m['link']['attribute'])) {
                        foreach ($m['link']['attribute'] as $key => $attribute) {
                            $result .= $key . '="' . $attribute . '" ';
                        }
                    }
                    $result .= '>';
                    $result .= $m['link']['text'];
                    $result .= '</a>';
                } else if (l_active($m['link']['url'], $exact)) {
                    // render title
                    $result .= '<span class="' . @implode(' ', $m['link']['class']) . '" ';
                    foreach ($m['link']['attribute'] as $key => $attribute) {
                        $result .= $key . '="' . $attribute . '" ';
                    }
                    $result .= '>';
                    $result .= $m['link']['text'];
                    $result .= '</span>';
                }
            } else {
                $result .= $m['title'];
            }


            // render menu
            $result .= render_menu($m['menu'], l_active($m['link']['url'], $exact));
        } else if (Auth::has_authentication($m['access'])) {
            // render li
            $li_classes = trim(@implode(' ', $m['class']) . (l_active($m['link']['url'], $exact) ? ' active' : '') . (l_active($m['link']['url'], true) ? ' current' : ''));
            if ($li_classes != '') {
                $result .= '<li class="' . $li_classes . '" ';
            } else {
                $result .= '<li ';
            }
            foreach ($m['attribute'] as $key => $attribute) {
                $result .= $key . '="' . $attribute . '" ';
            }
            $result .= '>';
            if (isset($m['value'])) {
                $result .= $m['value'];
            }

            // render element
            if ($m['link']) {
                // render link
                $result .= '<a href="' . $m['link']['url'] . '" class="' . @implode(' ', $m['link']['class']) . '" ';
                foreach ($m['link']['attribute'] as $key => $attribute) {
                    $result .= $key . '="' . $attribute . '" ';
                }
                $result .= '>';
                $result .= $m['link']['text'];
                $result .= '</a>';
            } else {
                $result .= $m['title'];
            }
        }
        $result .= '</li>';
    }
    $result .= '</ul>';
    return $result;
}

/**
 * Create a link. This is used especially for render_menu()
 * TODO: this is old and ugly and needs to be updated.
 * @param string $text the title of the link
 * @param string $url the link url
 * @param array $classes an array of classes to add to the link
 * @param array $attributes attributes to be added to the link
 * @param string $title the title of the link
 * @param string $alt the alt text of the link
 * @param string $id the id of the link
 * @return array the link object
 */
function l($text, $url = '#', $classes = array(), $attributes = array(), $title = '', $alt = '', $id = '') {
    return array(
        'text' => $text,
        'url' => $url,
        'class' => $classes,
        'id' => $id,
        'attribute' => $attributes,
        'title' => $title,
        'alt' => $alt
    );
}

/**
 * Check if a url is the same as the current url
 * @deprecated
 * @param string $uri the url to check
 * @param boolean $exact specifies if the link should be an exact match or if it can be a derivative of it e.g. google.com/ would be valid though the page were actually google.com/index.php
 * @return boolean
 */
function l_active($uri, $exact = false) {
    return Router::is_active_url($uri, $exact);
}

/**
 * convert a string from human readable to machine readable
 * @param string $human_name the human readable string
 * @return string the machine readable string
 */
function human_to_machine($human_name) {
    return strtolower(preg_replace(array(
        '/[^a-zA-Z0-9]+/',
        '/-+/',
        '/^-+/',
        '/-+$/',
    ), array(
        '-',
        '-',
        '',
        ''
    ), $human_name));
}

/**
 * convert a string from machine readable to human readable
 * @param string $machine_slug the machine readable string
 * @return string the human readable string
 */
function machine_to_human($machine_slug) {
    return ucfirst(preg_replace(array(
        '/\_/',
        '/-/',
    ), array(
        ' ',
        ' '
    ), $machine_slug));
}
  