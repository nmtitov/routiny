<?php

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
