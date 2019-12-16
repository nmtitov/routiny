<?php

/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class Rt {

    private $router;

    public function __construct() {
        $this->router = new RtRouter();
    }

    public function __call($name, $arguments) {
        if (RtHelper::checkHTTPMethod($name)) {
            if (isset($arguments[0])) {
                return $this->store($name, $arguments[0]);
            }
        } elseif (RtHelper::checkDecorator($name)) {
            if (isset($arguments[0])) {
                return $this->decorate($name, $arguments[0]);
            } else {
                return $this->decorate($name);
            }
        }
    }

    private function store($method, $route) {
        $this->router->saveRoute($method, $route);
        //defining a GET handler also automatically defines HEAD handler
        if ('GET' == $method) {
            $this->router->saveRoute('HEAD', $route);
        }
        return $this;
    }

    private function decorate($name, $params=null) {
        $this->router->saveDecoratorParams($name, $params);
        return $this;
    }

    public function format_by_request() {
        $type = $this->router->getType();
        if (!is_null($type)) {
            $this->decorate($type);
        } else {
            $this->decorate('view');
        }
        return $this;
    }

    public function dispatch($function) {
        $this->router->saveFunction($function);
        return $this;
    }

    public function run() {
        $action = $this->router->getAction();
        $decorators = $this->router->getDecorators();
        if (!empty($decorators)) {
            foreach ($decorators as $name => $params) {
                $decorator = 'Rt' . $name;
                $action = new $decorator($action, $params);
            }
        }
        echo $action->perform();
    }

}


/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class RtAction implements RtIAction {

    private $function;
    private $args;

    /**
     * Create action from function and its arguments
     * 
     * @param mixed $function
     * @param mixed $args
     */
    public function __construct($function, $args) {
        $this->function = $function;
        $this->args = $args;
    }

    /**
     * Run provided function with its arguments and return derived result
     * 
     * @return mixed
     */
    public function perform() {
        if (RtHelper::check_closure($this->function)) {
            if ($this->args) {
                $result = call_user_func_array(
                        array($this->function, '__invoke'), $this->args);
            } else {
                $function = $this->function;
                $result = $function();
            }
        } else if (RtHelper::check_array($this->function)
                || RtHelper::check_string($this->function)) {
            if ($this->args) {
                $result = call_user_func_array($this->function, $this->args);
            } else {
                $result = call_user_func($this->function);
            }
        }
        return $result;
    }

}


/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class RtException extends Exception {
    
}


/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class RtHelper {
    const D = "|";
    const HTTP_METHODS = "OPTIONS|GET|HEAD|POST|PUT|PATCH|TRACE|LINK|UNLINK";
    const DECORATORS = "VIEW|XML|XSLT|JSON|TEXT";
    const TYPES = "TEXT|XML|JSON";

    /**
     * Check if provided function name is real function defined in system
     * 
     * @param string $func_name
     * @return bool
     */
    public static function check_string($func_name) {
        return is_string($func_name) && function_exists($func_name);
    }

    /**
     * Check if provided array (object (or class) and method (can be static))
     * ['object','method'] were defined in application
     * 
     * @param string $array
     * @return bool
     */
    public static function check_array($array) {
        if (!is_null($array)) {
            if (is_array($array)) {
                if (isset($array[0]) && isset($array[1])) {
                    return method_exists($array[0], $array[1]);
                }
            }
        }
    }

    /**
     * Check if provided function is anonymous function (instance of Closure)
     *
     * @param Closure $closure
     * @return bool
     */
    public static function check_closure($closure) {
        if (!is_null($closure)) {
            return is_object($closure) && $closure instanceof Closure;
        }
    }

    public static function checkHTTPMethod($method) {
        return in_array($method, explode(RtHelper::D, strtolower(RtHelper::HTTP_METHODS)));
    }

    public static function checkDecorator($decorator) {
        return in_array($decorator, explode(RtHelper::D, strtolower(RtHelper::DECORATORS)));
    }

    public static function checkType($type) {
        return in_array(strtolower($type), explode(RtHelper::D, strtolower(RtHelper::DECORATORS)));
    }

}


/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
interface RtIAction {
    public function perform();
}


/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class RtJSON implements RtIAction {

    private $action;

    public function __construct(RtIAction $action) {
        $this->action = $action;
    }

    public function perform() {
        $action = $this->action;
        $result = $action->perform();
        return json_encode($result);
    }

}


class RtRouter {

    private $storage;
    private $last = array();
    private $request = array();
    private $stored_route;

    public function __construct() {
        $this->request['method'] = $this->getRequestMethod();
        $this->request['route'] = $this->getRequestRoute();
        $this->request['query'] = $this->getRequestQuery();
    }

    public function saveRoute($method, $route) {
        $this->last = array();
        if (!empty($method) AND !empty($route) AND is_string($method)
                AND is_string($route) AND !$this->duplicate($method, $route)) {
            $this->storage[$method][$route] = array();
            $this->last['method'] = $method;
            $this->last['route'] = $route;
        } else {
            throw new RtException("Bad params or duplicated route");
        }
    }

    private function duplicate($method, $route) {
        return isset($this->storage[$method][$route]);
    }

    public function saveFunction($function) {
        if ($this->checkFunc($function)) {
            $this->storage[$this->last['method']][$this->last['route']]['function'] = $function;
        } else {
            $msg = "Function must be Closure object, string name of existing function array(object, method) or array(class, static_method).";
            throw new RtException($msg);
        }
    }

    private function checkFunc($function) {
        return RtHelper::check_array($function)
        OR RtHelper::check_closure($function)
        OR RtHelper::check_string($function);
    }

    public function saveDecorator($name) {
        if (RtHelper::checkDecorator($name)) {
            $this->storage[$this->last['method']][$this->last['route']]['decorators'][$name] = array();
        } else {
            throw new RtException("Decorator $name wasn't found in list");
        }
    }

    public function saveDecoratorParams($name, $params) {
        if (RtHelper::checkDecorator($name)) {
            $this->storage[$this->last['method']][$this->last['route']]['decorators'][$name] = $params;
        } else {
            throw new RtException("Decorator $name wasn't found in list");
        }
    }

    public function getAction() {
        $stored_route = $this->getLatestMatch($this->request['route']);
        if (isset($this->storage[$this->request['method']][$stored_route]['function'])) {
            return new RtAction($this->storage[$this->request['method']][$stored_route]['function'],
                    $this->request['query']);
        } else {
            throw new RtException("Route {$this->request['method']} {$this->request['route']} doesnt match any pattern");
        }
    }

    public function getDecorators() {
        $stored_route = $this->getLatestMatch($this->request['route']);
        if (isset($this->storage[$this->request['method']][$stored_route]['decorators'])) {
            return $this->storage[$this->request['method']][$stored_route]['decorators'];
        }
    }

    private function getLatestMatch($path) {
        if (isset($this->storage[$this->request['method']])) {
            $found_route = null;
            foreach ($this->storage[$this->request['method']] as $key => $value) {
                if (preg_match($key, $path)) {
                    $found_route = $key;
                }
            }
            return $found_route;
        }
    }

    private function getRequestMethod() {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

    private function getRequestRoute() {
		$index = "/index.php";
		$request_uri = $_SERVER['REQUEST_URI'];
		$request_uri = str_replace($index, "", $request_uri);
		$route = strtolower(parse_url($request_uri, PHP_URL_PATH));
        return $route;
    }

    public function getType() {
        if (isset($this->request['route'])) {
            $route = $this->request['route'];
            if (is_string($route) && !empty($route)) {
                if (preg_match("/\.(.*)$/i", $route, $type)) {
                    if (is_array($type) && isset($type[0])) {
                        $found_type = strtolower(str_replace('.', '', $type[0]));
                        if (RtHelper::checkType($found_type)) {
                            return $found_type;
                        }
                    }
                }
            }
        }
    }

    private function getRequestQuery() {
        $query_string = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if (!empty($query_string)) {
            $pairs = explode("&", $query_string);
            if (!empty($pairs)) {
                $params = array();
                foreach ($pairs as $pair) {
                    list($k, $v) = array_map("urldecode", explode("=", $pair));
                    $params[$k] = $v;
                }
                return $params;
            }
        }
    }

}


/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class RtText implements RtIAction {

    private $action;

    public function __construct(RtIAction $action) {
        $this->action = $action;
    }

    public function perform() {
        $action = $this->action;
        $result = $action->perform();
        return serialize($result);
    }

}


/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class RtView implements RtIAction {

    private $action;
    private $template;

    public function __construct(RtIAction $action, $params=null) {
        $this->action = $action;
        if (isset($params['template'])) {
            $this->template = $template;
        }
    }

    public function perform() {
        $action = $this->action;
        $result = $action->perform();
        if (!is_null($this->template)) {
            echo 'hardcore templating action';
        } else {
            if (is_array($result)) {
                print_r($result);
            } else {
                echo $result;
            }
        }
    }

}


class RtXML implements RtIAction {

    private $action;
    private $root;

    public function __construct($action, $params=null) {
        $this->action = $action;
        if (isset($params['root'])) {
            $this->root = $params['root'];
        }
    }

    public function perform() {
        $action = $this->action;
        $data = $action->perform();
        $xml = self::toXML($data, $this->root);
        return $xml;
    }

    /**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
     *
     * @param array $data
     * @param string $rootNodeName - what you want the root node to be - defaults to data.
     * @param SimpleXMLElement $xml - should only be used recursively
     * @return string XML
     */
    public static function toXML($data, $format=true, $rootNodeName = 'ResultSet', &$xml=null) {
        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if (ini_get('zend.ze1_compatibility_mode') == 1)
            ini_set('zend.ze1_compatibility_mode', 0);
        if (is_null($xml))
            $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><xml/>');
        // loop through the data passed in.
        foreach ($data as $key => $value) {
            // no numeric keys in our xml please!
            if (is_numeric($key)) {
                $numeric = 1;
                $key = $rootNodeName;
            }
            // delete any char not allowed in XML element names
            $key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);
            // if there is another array found recrusively call this function
            if (is_array($value)) {
                $node = self::isAssoc($value) || $numeric ? $xml->addChild($key) : $xml;
                // recrusive call.
                if (isset($numeric) && 1 == $numeric) {
                    $key = 'anon';
                }
                self::toXml($value, $key, $node);
            } else {
                // add single node.
                $value = htmlentities($value);
                $xml->addChild($key, $value);
            }
        }
        if ($format) {
            $doc = new DOMDocument('1.0');
            $doc->preserveWhiteSpace = false;
            $doc->loadXML($xml->asXML());
            $doc->formatOutput = true;
            return $doc->saveXML();
        } else {
            return $xml->asXML();
        }
    }

    /**
     * Convert an XML document to a multi dimensional array
     * Pass in an XML document (or SimpleXMLElement object) and this recrusively loops through and builds a representative array
     *
     * @param string $xml - XML document - can optionally be a SimpleXMLElement object
     * @return array ARRAY
     */
    public static function toArray($xml) {
        if (is_string($xml))
            $xml = new SimpleXMLElement($xml);
        $children = $xml->children();
        if (!$children)
            return (string) $xml;
        $arr = array();
        foreach ($children as $key => $node) {
            $node = self::toArray($node);

            // support for 'anon' non-associative arrays
            if ($key == 'anon')
                $key = count($arr);

            // if the node is already set, put it into an array
            if (isset($arr[$key])) {
                if (!is_array($arr[$key]) || $arr[$key][0] == null)
                    $arr[$key] = array($arr[$key]);
                $arr[$key][] = $node;
            } else {
                $arr[$key] = $node;
            }
        }
        return $arr;
    }

    // determine if a variable is an associative array
    public static function isAssoc($array) {
        return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
    }

}


/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nmtitov@ya.ru>
 */
class RtXSLT implements RtIAction {

    private $action;
    private $stylesheet;

    public function __construct(RtIAction $action, $params=null) {
        $this->action = $action;
        if (isset($params['stylesheet'])) {
            $this->stylesheet = $params['stylesheet'];
        }
    }

    public function perform() {
        $action = $this->action;
        $xml_str = $action->perform();
        $xml_doc = simplexml_load_string($xml_str);
        if (!is_null($this->stylesheet)) {
            $xp = new XsltProcessor();
            $xsl = new DomDocument;
            $xsl->load($this->stylesheet);
            $xp->importStylesheet($xsl);
            if ($html = $xp->transformToXML($xml_doc)) {
                return $html;
            } else {
                throw new RtException('XSL transformation failed.');
            }
        } else {
            throw new RtException("Couldn't go on without xslt");
        }
    }

}
