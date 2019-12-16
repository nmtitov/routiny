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
