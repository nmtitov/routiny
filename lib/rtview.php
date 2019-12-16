<?php

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
