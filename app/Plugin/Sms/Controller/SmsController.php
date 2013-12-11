<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('HttpSocket', 'Network/Http');

class SmsController extends SmsAppController {
    public $uses = array(
        'Sms.SmsMessage',
        'Sms.SmsLog',
        'Sms.SmsResponse',
        'ConfigItem'
    );

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('receive');
        $this->bodyTitle = 'Settings';
        $this->Navigation->addCrumb('Settings', array('controller' => '../Setup', 'action' => 'index'));
    }

    public function receive(){
        $provider = $this->request->params['pass'][0];

        switch ($provider) {
            case "smsdome":
                $this->smsdome();
                break;
            default:
                echo "Incorrect provider";
                break;
        }

        $this->autoRender = false;
    }

    public function smsdome(){
        $number = $this->request['url']['mobileno'];
        $message = $this->request['url']['message'];

        $responses = $this->SmsResponse->find('all', array('conditions'=>array('number'=>$number)));
        $messages = $this->SmsMessage->find('all', array('conditions'=>array('enabled'=>1), 'order'=>array('order'), 'recursive'=>-1));

        $providerUrl = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_provider_url'));
        $smsNumberField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_number'));
        $smsContentField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_content'));
    
        if(empty($responses)){
            $firstMessage = $messages[0]['SmsMessage'];
            $data[] = array(
                'SmsResponse' => array(
                    'message' => $firstMessage['message'],
                    'sent' => date('Y-m-d h:i:s'),
                    'received' => date('Y-m-d h:i:s'),
                    'number' => $number,
                    'response' => rawurldecode($message),
                    'order' => $firstMessage['order']
                )
            );

            $logData[] = array(
                'SmsLog' => array(
                    'send_receive' => 2,
                    'created' => date('Y-m-d h:i:s'),
                    'number' => $number,
                    'message' => rawurldecode($message)
                )
            );

            $followingMessage = isset($messages[1]['SmsMessage']) ? $messages[1]['SmsMessage'] : null;
       
            if(!empty($followingMessage)){
                $param = array($smsNumberField => $number, $smsContentField => $followingMessage['message']);
                $HttpSocket = new HttpSocket();
                $results = $HttpSocket->post($providerUrl, $param);
                $response = json_decode($HttpSocket->response, true);
                if($response['result']['status'] == "OK"){

                    $data[] = array(
                        'SmsResponse' => array(
                            'message' => $followingMessage['message'],
                            'sent' => date('Y-m-d h:i:s'),
                            'number' => $number,
                            'order' => $followingMessage['order']
                        )
                    );

                    $logData[] = array(
                        'SmsLog' => array(
                            'send_receive' => 1,
                            'created' => date('Y-m-d h:i:s'),
                            'number' => $number,
                            'message' => $followingMessage['message']
                        )
                    );

                    pr(__('Sent'));
                }else{
                    pr($response['result']['error']);
                }
            }
            $this->SmsResponse->saveAll($data);
            $this->SmsLog->saveAll($logData);
        }else{
            $lastResponse = end($responses);
            $lastResponse = $lastResponse['SmsResponse'];
            $data[] = array(
                'SmsResponse' => array(
                    'id' => $lastResponse['id'],
                    'received' => date('Y-m-d h:i:s'),
                    'response' => rawurldecode($message)
                )
            );

            $logData[] = array(
                'SmsLog' => array(
                    'send_receive' => 2,
                    'created' => date('Y-m-d h:i:s'),
                    'number' => $number,
                    'message' => rawurldecode($message)
                )
            );

            $followingMessage = isset($messages[$lastResponse['order']]['SmsMessage']) ? $messages[$lastResponse['order']]['SmsMessage'] : null;
            if(!empty($followingMessage)){
                $param = array($smsNumberField => $number, $smsContentField => $followingMessage['message']);
                $HttpSocket = new HttpSocket();
                $results = $HttpSocket->post($providerUrl, $param);
                $response = json_decode($HttpSocket->response, true);
                if($response['result']['status'] == "OK"){
                    $data[] = array(
                        'SmsResponse' => array(
                            'message' => $followingMessage['message'],
                            'sent' => date('Y-m-d h:i:s'),
                            'number' => $number,
                            'order' => $followingMessage['order']
                        )
                    );

                     $logData[] = array(
                        'SmsLog' => array(
                            'send_receive' => 1,
                            'created' => date('Y-m-d h:i:s'),
                            'number' => $number,
                            'message' => $followingMessage['message']
                        )
                    );
                    pr(__('Sent'));
                }else{
                    pr($response['result']['error']);
                }
            }
            $this->SmsResponse->saveAll($data);
            $this->SmsLog->saveAll($logData);
        }


        $this->autoRender = false;
    }

    public function index(){
        return $this->redirect(array('action'=>'messages'));
    }
    public function messages() {
        $this->Navigation->addCrumb('Messages');

        $data = $this->SmsMessage->find('all', array('order'=>array('SmsMessage.order ASC')));
        $this->set('data', $data);
    }

    public function messagesAdd(){
        $this->Navigation->addCrumb('Add Messages');
        if($this->request->is('post')) { // save
           $data = $this->data['SmsMessage'];
            $this->SmsMessage->create();
            if($this->SmsMessage->save($data)){
                if($data['original_order'] != $data['order']){
                    $this->SmsMessage->updateAll(
                        array('SmsMessage.order' => $data['original_order']),
                        array('SmsMessage.order' => $data['order'], array('NOT'=>array('SmsMessage.id'=>array($this->SmsMessage->getLastInsertId()))))
                    );
                }
                
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'messages'));
            }
        }

        $orders = $this->SmsMessage->find('count');
        $orderOptions[1] = 1;
        for($i=1;$i<=$orders;$i++){
            $orderOptions[$i] = $i;
        }
        $orderOptions[$i] = $i;
        $this->set('defaultOrder', end($orderOptions));
        $this->set('orderOptions', $orderOptions);

    }
    
    public function messagesView() {
        $id = $this->params['pass'][0];
        $obj = $this->SmsMessage->find('all',array('conditions'=>array('SmsMessage.id' => $id)));
        
        if(!empty($obj)) {
            $this->Navigation->addCrumb('Message Details');
            
            $this->Session->write('SmsMessageId', $id);
            $this->set('obj', $obj);
        } 
    }

    public function messagesEdit() {
        $id = $this->params['pass'][0];
        if($this->request->is('get')) {
            $obj = $this->SmsMessage->find('first',array('conditions'=>array('SmsMessage.id' => $id)));
  
            if(!empty($obj)) {
                $this->Navigation->addCrumb('Edit Message Details');
                $this->request->data = $obj;
               
            }
         } else {
            $data = $this->data['SmsMessage'];

            if ($this->SmsMessage->save($data)){
                if($data['original_order'] != $data['order']){
                    $this->SmsMessage->updateAll(
                        array('SmsMessage.order' => $data['original_order']),
                        array('SmsMessage.order' => $data['order'], array('NOT'=>array('SmsMessage.id'=>array($id))))
                    );
                }
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'messagesView', $data['id']));
            }
         }

        $orders = $this->SmsMessage->find('count');
        $orderOptions[1] = 1;
        for($i=1;$i<=$orders;$i++){
            $orderOptions[$i] = $i;
        }

        $this->set('orderOptions', $orderOptions);
        $this->set('id', $id);
       
    }

    public function messagesDelete($id) {
        if($this->Session->check('SmsMessageId')) {
            $id = $this->Session->read('SmsMessageId');
            $name = $this->SmsMessage->field('message', array('SmsMessage.id' => $id));
            $order = $this->SmsMessage->field('order', array('SmsMessage.id' => $id));

            $this->SmsMessage->delete($id);
            $this->SmsMessage->updateAll(
            array(
              'SmsMessage.order' => 'SmsMessage.order-1'
            ), 
            array('SmsMessage.order >' => $order));
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'messages'));
        }
    }

     public function logs($selectedType=null) {
        $this->Navigation->addCrumb('Logs');

        $conditions = array();
        if(!empty($selectedType)){
            $conditions['send_receive'] = $selectedType;
        }

        $data = $this->SmsLog->find('all', array('order'=>array('SmsLog.created DESC'), 'conditions'=>$conditions));
        $this->set('data', $data);

        $typeOptions = array('1'=>__('Sent'), '2'=>__('Received'));
        $this->set('typeOptions', $typeOptions);
        $this->set('selectedType', $selectedType);
    }

    
    public function logsDelete() {
        $this->SmsLog->truncate();
        $this->Utility->alert('All logs have been deleted successfully.');
        $this->redirect(array('action' => 'logs'));
        
    }

    public function responses() {
        $this->Navigation->addCrumb('Responses');

        $data = $this->SmsResponse->find('all', array('order'=>array('SmsResponse.number, SmsResponse.order ASC')));
        $this->set('data', $data);
    }

    
    public function responsesDelete() {
        $this->SmsResponse->truncate();
        $this->Utility->alert('All responses have been deleted successfully.');
        $this->redirect(array('action' => 'responses'));
        
    }

}