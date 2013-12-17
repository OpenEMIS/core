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
        $this->bodyTitle = 'Administration';
        $this->Navigation->addCrumb('Administration', array('controller' => '../Setup', 'action' => 'index'));
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

            $this->SmsResponse->saveAll($data);

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

        $data = $this->SmsLog->find('all', array('order'=>array('SmsLog.id DESC'), 'conditions'=>$conditions));
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

        /*$maxMessages = $this->SmsResponse->find('first', array(
        'fields' => array('MAX(SmsResponse.order) AS maxOrder')
        ));*/
        $maxMessages = $this->SmsMessage->find('first', array(
        'fields' => array('MAX(SmsMessage.order) AS maxOrder'),
        'conditions'=>array('enabled'=>1)
        ));

        $max = 1;
        if(!empty($maxMessages)){
            $max = $maxMessages[0]['maxOrder'];
        }
        $messages =  $this->SmsMessage->find('all', array(
        'conditions'=>array('enabled'=>1),
        'order'=>array('order'),
        'recursive'=>-1
        ));
        $data = $this->SmsResponse->getColumnFormat($max);
        $this->set('max', $max);
        $this->set('data', $data);
        $this->set('messages', $messages);
    }

    public function responsesDownload(){
        $this->autoRender = false;
        $maxMessages = $this->SmsMessage->find('first', array(
        'fields' => array('MAX(SmsMessage.order) AS maxOrder'),
        'conditions'=>array('enabled'=>1)
        ));

        $max = 1;
        if(!empty($maxMessages)){
            $max = $maxMessages[0]['maxOrder'];
        }
        $data = $this->SmsResponse->getColumnFormat($max);
        $messages =  $this->SmsMessage->find('all', array(
        'conditions'=>array('enabled'=>1),
        'order'=>array('order'),
        'recursive'=>-1
        ));


        $fieldName = null;
        $result = null;

        foreach($messages as $value){
            $fieldName[] =  str_replace(',', ' ', $value['SmsMessage']['message']);
        }
       
        if(!empty($fieldName)){
             $fieldName[count($fieldName)-1] = end($fieldName) . "\n";
        }
        
        if(!empty($data)){

           foreach($data as $obj){
                foreach($obj as $key=>$value){
                    if(isset($value['number'])){
                        $result[] = $value['number'];
                    }
                   $result[] = str_replace(',', ' ',array_pop(array_values($value)));
                }
            }
        }

        echo $this->download_csv_results($result, $fieldName, 'sms_responses_' . date('Ymdhis'));
        exit;
    }
    
    public function responsesDelete() {
        $this->SmsResponse->truncate();
        $this->Utility->alert('All responses have been deleted successfully.');
        $this->redirect(array('action' => 'responses'));
    }

 
    function download_csv_results($results, $fieldName=NULL, $name = NULL)
    {
        if( ! $name)
        {
            $name = md5(uniqid() . microtime(TRUE) . mt_rand()). '.csv';
        }

        header('Expires: 0');
        header('Content-Encoding: UTF-16');
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv; charset=UTF-16');
        header('Content-Disposition: attachment; filename='. $name);
        header('Content-Transfer-Encoding: binary'); 

        $outstream = fopen("php://output", "w");

        //add BOM to fix UTF-8 in Excel
        fputs($outstream, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
        //fwrite($outstream, "xEFxBBxBF");
        //fputs($outstream, "\xEF\xBB\xBF"); 

        //echo "\xEF\xBB\xBF";

        /*foreach($results as $result)
        {
            fputcsv($outstream, $result);
        }*/
        fputcsv($outstream,$fieldName);
        if(!empty($results)){
            fputcsv($outstream,$results);  
        }
        fclose($outstream);
    }

}