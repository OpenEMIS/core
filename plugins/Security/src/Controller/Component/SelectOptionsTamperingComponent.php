<?php
namespace Security\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Controller\Exception\AuthSecurityException;

class SelectOptionsTamperingComponent extends Component {
    const DEFAULT_MESSAGE = 'Dropdown Options has been tampered';

    public function startup(Event $event) {
        // Select options form tampering
        $session = $this->request->session();
        if ($session->check('FormTampering')) {
            if ($this->request->is(['post', 'put', 'delete'])) {
                $formTamperingSession = $session->read('FormTampering');
                $formTamperingReload = $session->read('FormTamperingReload');
                $requestData = $this->request->data;

                $msg = [];
                if ($requestData !== $formTamperingReload) {
                    $msg = $this->multiDiff($formTamperingSession, $requestData);
                }
                if (!empty($msg)) {
                    $exceptionMessage = implode(', ', $msg);
                    throw new AuthSecurityException($exceptionMessage.' - '.self::DEFAULT_MESSAGE);
                } else {
                    $session->delete('FormTampering');
                    $session->write('FormTamperingReload', $requestData);
                }
            } else if (!$this->request->is('ajax')) {
                $session->delete('FormTampering');
                $session->delete('FormTamperingReload');
            }
        } 
    }

    public function multiDiff($arr1, $arr2, $keyName = '') {
        $result = [];
        foreach ($arr1 as $k => $v){
            $tmpKeyName = "$keyName.$k";
            if(isset($arr2[$k])) {
                if (is_array($v) && is_array($arr2[$k])) {
                    $diffResult = $this->multiDiff($v, $arr2[$k], $tmpKeyName);
                    $result = $result + $diffResult;
                } else {
                    // all comparison value to string so that in_array strict mode can check
                    // if both values are the same
                    $v = array_map('strval', $v);
                    if (!in_array(strval($arr2[$k]), $v, true)) {
                        $result[] = substr($tmpKeyName, 1);
                    }
                }
            }
        }
        return $result;
    }
}