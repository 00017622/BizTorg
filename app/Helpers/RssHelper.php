<?php
namespace App\Helpers;

use SimpleXMLElement;

class ExtendedSimpleXMLElement extends ExtendedSimpleXMLElement
{
    public function addCData($value)
    {
        $node = dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($value));
    }
}
