<?php

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
