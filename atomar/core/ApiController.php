<?php

namespace atomar\core;

/**
 * The api extends the controller to provide some advanced functionality to the api controllers.
 * New api method can be easily defined by adding the prefix "get_" or "post_". Any non-null parameters will be
 * required and the API will automatically notify clients if parameters are missing or malformed.
 */
abstract class ApiController extends Controller {
  private $api_version = '1.0.0';

  function __construct($return_url) {
    parent::__construct($return_url);
  }

  /**
   * This method will be called automatically to handle any exceptions in the class
   *
   * @param \Exception $e the exception
   */
  public function exception_handler($e) {
    Logger::log_error($e->getMessage(), $e->getTrace());
    render_json(array('status' => 'error', 'message' => get_class($this) . '->exception_handler: An exception has occurred. See the log for details.'));
  }

  /**
   * Handles all the get requests and provides some basic error handling
   * If you need to perform some operations before hand use setup_get() so you don't to re-write this method
   * @param array $matches
   */
  public function GET($matches = array()) {
    $this->setup_get($matches);
    $this->api_handler('get', $matches);
  }

  /**
   * Allows you to perform any additional actions before get requests are processed
   * @param array $matches
   */
  protected abstract function setup_get($matches = array());

  /**
   * Performs the api handling including name based method parameterisation
   * @param string $prefix the api method prefix
   * @param array $matches the matched parameters from the url route
   */
  private final function api_handler($prefix, $matches) {
    $api = $matches['api'];
    $method_signature = $prefix . '_' . $api;
    $backwards_compatible_method_signature = strtoupper('_' . $prefix);
    if (method_exists($this, $method_signature)) {
      // execute the api method
      error_reporting(0); // make sure errors do not corrupt the response.
      try {
        $response = $this->call_user_func_args($method_signature, $_REQUEST, true);
        $this->respond($response);
      } catch (ParameterException $e) {
        // send any parameter parsing exceptions as an error response
        $this->respond(new MalformedParameters($e->getMessage()));
      }
    } elseif (method_exists($this, $backwards_compatible_method_signature)) {
      // run backwards compatible methods e.g. _POST and _GET if they exist.
      // Exceptions in the backwards compatible methods will bubble up.
      $this->$backwards_compatible_method_signature($matches);
    } else {
      // send error response
      $this->respond(new UnknownAPIError('The method signature "' . $method_signature . '" could not be found for the api call "' . $api . '"'));
    }
  }

  /**
   * Calls a function with named arguments.
   * Just written and quite tested. If you find bugs, please provide feedback and I'll update the code.
   * In a sane usage scenario, it will work. If you try your best, you might break it :)
   * If true, $ValidateInput tries to warn you of issues with your Arguments, bad types, nulls where they should not be.
   *
   * @copyright Claudrian
   * @param string $method
   * @param array $arguments
   * @param bool $validate_input
   * @return mixed
   * @throws ParameterException
   * @throws \Exception
   */
  private final function call_user_func_args($method, array $arguments, $validate_input = false) {
    // Make sure the $method actually exists
    if (!method_exists($this, $method)) {
      throw new ParameterException($method . ' is not defined.');
    }

    // validate arguments
    array_change_key_case($arguments, CASE_LOWER);
    foreach ($arguments as $argument_name => $argument_value) {
      if (empty($argument_name) or is_numeric($argument_name)) {
        throw new ParameterException('$Arguments cannot have numeric offsets.');
      }
      if (!preg_match('~^[a-z_][a-z0-9_]*$~', $argument_name)) {
        throw new ParameterException('$Arguments contains illegal character offsets. Was given "' . $argument_name . '"');
      }
    }

    $reflector = new \ReflectionMethod(get_class($this), $method);

    // No arguments, and no required arguments
    $required_parameter_count = $reflector->getNumberOfRequiredParameters();
    if (empty($arguments) && $required_parameter_count == 0) {
      return $this->$method();
    }

    // Arguments, but no method arguments
    $parameter_count = $reflector->getNumberOfParameters();
    if (!$parameter_count) {
      return $this->$method();
    }

    // Prepare the $Parameters
    $parameters = array();
    foreach ($reflector->getParameters() as $param) {
      $lower_name = strtolower($name = $param->getName());
      $argument = ($available = array_key_exists($name, $arguments)) ? $arguments[$name] : null;
      $default = ($is_default = $param->isDefaultValueAvailable()) ? $param->getDefaultValue() : null;
      $parameters[$lower_name] = array('Name' => $name, 'Offset' => $param->getPosition(), 'Optional' => $param->isOptional(), 'Nullable' => $param->allowsNull(), 'Reference' => $param->isPassedByReference(), 'Array' => $param->isArray(), 'Defaultable' => $is_default, 'Default' => $default, 'Available' => $available, 'Provided' => $available ? $argument : $default,);
    }

    // Pop pointless nulls (from the last to the first)
    end($parameters);
    while ($param = current($parameters)) {
      if (!$param['Nullable'] or !$param['Optional'] or !is_null($param['Provided'])) {
        break;
      }
      array_pop($parameters); // Pop trailing null optional nullable arguments
      prev($parameters); // Move one back
    }

    // Prepare the final $Arguments
    $arguments = array();
    foreach ($parameters as $name => $param) {
      if ($validate_input) {
        if (is_null($param['Provided']) and !$param['Nullable']) {
          throw new ParameterException("Argument '{$name}' does not accept NULL.");
        }
        if ($param['Array'] and !is_array($param['Provided'])) {
          if (!$param['Nullable'] and is_null($param['Provided'])) {
            throw new ParameterException("Argument '{$name}' should be an array.");
          }
        }
        if (!$param['Available'] and !$param['Optional'] and !$param['Defaultable']) {
          throw new ParameterException("Argument '{$name}' is required.");
        }
      }
      // Store this in the final $Arguments array
      $arguments[] = $param['Provided'];
    }
    // Invoke the actual function
    return $reflector->invokeArgs($this, $arguments);
  }

  /**
   * Handles the API responses
   *
   * @param mixed $data an array of data or an error object.
   */
  protected function respond($data) {
    $response = array('v' => $this->api_version, // api version
      't' => microtime(true)// time stamp
    );

    if (is_a($data, 'atomar\core\APIError')) {
      $response['error'] = array('type' => $data->getType(), 'message' => $data->getMessage());
      if ($data->getCode() != 0) {
        http_response_code($data->getCode());
      }
    } else {
      if (!is_array($data)) {
        // put string responses into the message
        $data = array('message' => $data);
      }
      $response['ok'] = $data;
    }
    render_json($response);
  }

  /**
   * Handles all the post requests and provides some basic error handling.
   * If you need to perform some operations before hand use setup_post() so you don't have to re-write this method
   * @param array $matches
   */
  public function POST($matches = array()) {
    $this->setup_post($matches);
    $this->api_handler('post', $matches);
  }

  /**
   * Allows you to perform any additional actions before post requests are processed
   * @param array $matches
   */
  protected abstract function setup_post($matches = array());

  /**
   * Handles all the options requests and provides some basic error handling.
   * If you need to perform some operations before hand use setup_options() so you don't have to re-write this method.
   * @param array $matches
   */
  public function OPTIONS($matches = array()) {
    $this->setup_options($matches);
    $this->api_handler('options', $matches);
  }

  /**
   * Allows you to perform any additional actions before options requests are processed
   * @param array $matches
   * @return mixed
   */
  protected function setup_options($matches = array()) {
    // This is overridable, but not often used so we don't make it abstract.
  }

  /**
   * Handles all the put requests and provides some basic error handling.
   * If you need to perform some operations before hand use setup_put() so you don't have to re-write this method.
   * @param array $matches
   */
  public function PUT($matches = array()) {
    $this->setup_put($matches);
    $this->api_handler('put', $matches);
  }

  /**
   * Allows you to perform any additional actions before put requests are processed
   * @param array $matches
   */
  protected function setup_put($matches = array()) {
    // This is overridable, but not often used so we don't make it abstract.
  }

  /**
   * Handles all the delete requests and provides some basic error handling.
   * If you need to perform some operations before hand use setup_delete() so you don't have to re-write this method.
   * @param array $matches
   */
  public function DELETE($matches = array()) {
    $this->setup_delete($matches);
    $this->api_handler('delete', $matches);
  }

  /**
   * Allows you to perform any additional actions before delete requests are processed
   * @param array $matches
   */
  protected function setup_delete($matches = array()) {
    // This is overridable, but not often used so we don't make it abstract.
  }

  /**
   * Sets the code version of the api that is sent with requests.
   * Providing an accurate api version allows clients to handle updates to the api.
   * @param string $version a version code in the format x.x.x
   */
  protected function set_api_version($version) {
    $this->api_version = $version;
  }

  /**
   * Renders the view and does some extra processing
   * @param string $view the relative path to the view that will be rendered
   * @param array $args custom options that will be sent to the view
   * @param array $options optional rules regarding how the template will be rendered.
   * @return string the rendered html
   */
  protected function renderView($view, $args = array(), $options = array()) {
    if (!isset($options['_controller'])) $options['_controller']['type'] = 'api';
    return parent::renderView($view, $args, $options);
  }

  /**
   * Utility method to populate the $_REQUEST object with data from php://input
   * This is usefull when performing operatins using PUT and DELETE
   */
  protected final function request_from_input_stream() {
    $data = file_get_contents("php://input");
    $args = explode('&', $data);
    foreach ($args as $arg) {
      $segments = explode('=', $arg);
      if (count($segments) !== 2) {
        $_REQUEST[$arg] = true;
      } else {
        list($key, $value) = $segments;
        $_REQUEST[$key] = $value;
      }
    }
  }

}

class ParameterException extends \Exception {
}

/**
 * Base class for api errors
 *
 */
class APIError {
  protected $message = 'An unknown error has occurred';
  protected $code = 0;

  /**
   * Creates a new api error
   * @param $message
   * @param int $code
   */
  function __construct($message, $code = 0) {
    $this->message = $message;
    $this->code = $code;
  }

  /**
   * get the error message
   *
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * get the error type
   *
   * @return string
   */
  public function getType() {
    return get_class($this);
  }

  /**
   * get the hreader response code
   *
   * @return mixed
   */
  public function getCode() {
    return $this->code;
  }
}

/**
 * Class UnknownAPIError used when an api method cannot be found
 */
class UnknownAPIError extends APIError {
}

/**
 * Class ServerError used when an exception occurs
 */
class ServerError extends APIError {
}

/**
 * Class MalformedParameters
 */
class MalformedParameters extends APIError {
}