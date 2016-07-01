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
            $formTamperingSession = $session->read('FormTampering');
            $requestData = $this->request->data;
            // pr('here');
            $formTamperingKeys = array_keys($formTamperingSession);
            $msg = [];
            foreach ($formTamperingKeys as $formTamperingKey) {
                $intersectFields = [];
                if (isset($requestData[$formTamperingKey])) {
                    $intersectFields = array_intersect_key($requestData[$formTamperingKey], $formTamperingSession[$formTamperingKey]);
                }
                
                foreach ($intersectFields as $key => $value) {
                    if (!in_array($value, $formTamperingSession[$formTamperingKey][$key])) {
                        $msg[] = "$formTamperingKey.$key";
                    }
                }
            }

            if (!empty($msg)) {
                $exceptionMessage = implode(', ', $msg);

                throw new AuthSecurityException($exceptionMessage.' - '.self::DEFAULT_MESSAGE);
            }
        } 
    }
}