<?php

/**
 * @package Routiny
 * @copyright &copy; 2010
 * @author Nikita Titov <nikita@zencode.ru>
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
