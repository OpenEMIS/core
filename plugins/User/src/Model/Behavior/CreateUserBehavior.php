<?php
namespace User\Model\Behavior;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\Association\BelongsTo;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Datasource\Exception\MissingModelException;
use DOMDocument;
class CreateUserBehavior extends Behavior {

    /**
     * National number is a valid number for JordanCSPD using cspd api 
     * @author Megha Gupta <megha.gupte@mail.valuecoders.com>
     * @return xml string 
     * @ticket POCOR-7727
     **/
    
    public function getResponseForJordanCSPD($soapUrl, $soapUser, $soapPassword, $xml_post_string)
    {
        $headers = array(
            "Content-type: application/soap+xml;charset=\"utf-8\"",
            "Accept: application/soap+xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/IVitalEvents/gePersonal",
            "Content-length: " . strlen($xml_post_string),
        ); //SOAPAction: your op URL

        $url = $soapUrl;
        // PHP cURL  for https connection with auth
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $soapUser . ":" . $soapPassword); // username and password - declared at the top of the doc
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // converting
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * Check if response from getResponseForJordanCSPD() contain national number node 'a:NAT_NO'
     * @author Megha Gupta <megha.gupte@mail.valuecoders.com>
     * @return xml string 
     * @ticket POCOR-7727
     **/
     
    public function getXmlResponseTextNodeCount($xml)
    {
        // Load the SOAP response into a DOMDocument
        $dom = new DOMDocument();
        $dom->loadXML($xml);

       // Call the function to search for <a:NAT_NO> within all child nodes
        $nodeName = 'a:NAT_NO'; // Replace with the actual node name
        $nodeContent = $this->findNode($dom->documentElement, $nodeName);

        if ($nodeContent !== null) {
            return true;//contain national number
        } else {
           return false;//does not contain national number
        }
    }
    
    /**
     * Function to recursively search for the desired node
     * * @author Megha Gupta <megha.gupte@mail.valuecoders.com>
     * @return xml string 
     * @ticket POCOR-7727
     **/
    
    public function findNode($node, $nodeName){
        if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === $nodeName) {
            return $node->textContent;
        }
        foreach ($node->childNodes as $childNode) {
            $result = $this->findNode($childNode, $nodeName);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }
}