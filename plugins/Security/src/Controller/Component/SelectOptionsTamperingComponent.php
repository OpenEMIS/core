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
        if ($session->check('FormTampering') && $this->request->is(['post', 'put', 'delete'])) {
            $formTamperingSession = $session->read('FormTampering');
            $formTamperingReload = $session->read('FormTamperingReload');
            $requestData = $this->request->data;
            $msg = [];
            if ($requestData !== $formTamperingReload) {
                $formTamperingKeys = array_keys($formTamperingSession);
                foreach ($formTamperingKeys as $formTamperingKey) {
                    $intersectFields = [];
                    if (isset($requestData[$formTamperingKey]) && is_array($requestData[$formTamperingKey])) {
                        $intersectFields = array_intersect_key($requestData[$formTamperingKey], $formTamperingSession[$formTamperingKey]);
                        foreach ($intersectFields as $key => $value) {
                            if (!in_array($value, $formTamperingSession[$formTamperingKey][$key])) {
                                $msg[] = "$formTamperingKey.$key";
                            }
                        }
                    } else {
                        if (isset($requestData[$formTamperingKey]) && !in_array($requestData[$formTamperingKey], $formTamperingSession[$formTamperingKey])) {
                            $msg[] = $formTamperingKey;
                        }
                    }
                }
            }

            if (!empty($msg)) {
                $exceptionMessage = implode(', ', $msg);
                throw new AuthSecurityException($exceptionMessage.' - '.self::DEFAULT_MESSAGE);
            } else {
                $session->delete('FormTampering');
                $session->write('FormTamperingReload', $requestData);
            }
        }
    }
}