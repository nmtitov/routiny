<?php

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
