<?php
$xml = Xml::fromArray(['response' => $data]);
echo $xml->asXML();