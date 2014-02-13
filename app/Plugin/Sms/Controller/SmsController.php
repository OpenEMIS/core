<?php
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
        $provider = $this->params['pass'][0];

        $this->received($provider);

        $this->autoRender = false;
    }

    public function received($provider){
        $number = $this->request['url']['mobileno'];
        $message = $this->request['url']['message'];
        $key = $this->request['url']['key'];

        $responses = $this->SmsResponse->find('all', array('conditions'=>array('number'=>$number)));
        $messages = $this->SmsMessage->find('all', array('conditions'=>array('enabled'=>1), 'order'=>array('order'), 'recursive'=>-1));

        $keyCompare = AuthComponent::password($provider);
        if($key == $keyCompare){
            if(empty($responses)){
                $firstMessage = $messages[0]['SmsMessage'];
                $data[] = array(
                    'SmsResponse' => array(
                        'message' => $firstMessage['message'],
                        'sent' => date('Y-m-d H:i:s'),
                        'received' => date('Y-m-d H:i:s'),
                        'number' => $number,
                        'response' => rawurldecode($message),
                        'order' => $firstMessage['order']
                    )
                );

                $logData[] = array(
                    'SmsLog' => array(
                        'send_receive' => 2,
                        'created' => date('Y-m-d H:i:s'),
                        'number' => $number,
                        'message' => rawurldecode($message)
                    )
                );

                $followingMessage = isset($messages[1]['SmsMessage']) ? $messages[1]['SmsMessage'] : null;

                $this->SmsResponse->saveAll($data);
                $this->SmsLog->saveAll($logData);
                
                if(!empty($followingMessage)){
                    $this->sent($provider, $number, $followingMessage);
                }
            }else{
                $lastResponse = end($responses);
                $lastResponse = $lastResponse['SmsResponse'];

                $data[] = array(
                    'SmsResponse' => array(
                        'id' => $lastResponse['id'],
                        'received' => date('Y-m-d H:i:s'),
                        'response' => rawurldecode($message)
                    )
                );

                $logData[] = array(
                    'SmsLog' => array(
                        'send_receive' => 2,
                        'created' => date('Y-m-d H:i:s'),
                        'number' => $number,
                        'message' => rawurldecode($message)
                    )
                );

                $followingMessage = null;
                foreach($messages as $message){
                    if($message['SmsMessage']['order']== $lastResponse['order']+1){
                        $followingMessage = $message['SmsMessage'];
                        break;
                    }
                }

                $this->SmsResponse->saveAll($data);
                $this->SmsLog->saveAll($logData);

                if(!empty($followingMessage)){
                   $this->sent($provider, $number, $followingMessage);
                }
            }
        }else{
            echo 0;
        }

        $this->autoRender = false;
    }

    public function sent($provider, $number, $followingMessage){
        $providerUrl = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_provider_url'));
        $smsNumberField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_number'));
        $smsContentField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_content'));
    
        $param = array($smsNumberField => $number, $smsContentField => $followingMessage['message']);
        $HttpSocket = new HttpSocket();
        $results = $HttpSocket->post($providerUrl, $param);

        $data = array();
        $logData = array();
        switch ($provider) {
            case "smsdome":
                $response = json_decode($HttpSocket->response, true);
                if($response['result']['status'] == "OK"){

                    $data = array(
                        'SmsResponse' => array(
                            'message' => $followingMessage['message'],
                            'sent' => date('Y-m-d H:i:s'),
                            'number' => $number,
                            'order' => $followingMessage['order']
                        )
                    );

                    $logData = array(
                        'SmsLog' => array(
                            'send_receive' => 1,
                            'created' => date('Y-m-d H:i:s'),
                            'number' => $number,
                            'message' => $followingMessage['message']
                        )
                    );


                    echo 1;
                }else{
                    $this->log($response, 'sms');
                    echo 0;
                }
                break;
            default:
                echo "Incorrect provider";
                break;
        }

        $this->SmsResponse->saveAll($data);
        $this->SmsLog->saveAll($logData);
      
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
        'fields' => array('MAX(SmsMessage.order) AS maxOrder,MIN(SmsMessage.order) AS minOrder'),
        'conditions'=>array('enabled'=>1)
        ));

        $max = 1;
        $min = 1;
        if(!empty($maxMessages)){
            $min = $maxMessages[0]['minOrder'];
            $max = $maxMessages[0]['maxOrder'];
        }
        $messages =  $this->SmsMessage->find('all', array(
        'conditions'=>array('enabled'=>1),
        'order'=>array('order'),
        'recursive'=>-1
        ));
        $data = $this->SmsResponse->getColumnFormat($min, $max);
        $this->set('min', $min);
        $this->set('max', $max);
        $this->set('data', $data);
        $this->set('messages', $messages);
    }

    public function responsesDownload(){
        $this->autoRender = false;
        $maxMessages = $this->SmsMessage->find('first', array(
        'fields' => array('MAX(SmsMessage.order) AS maxOrder,MIN(SmsMessage.order) AS minOrder'),
        'conditions'=>array('enabled'=>1)
        ));
        $max = 1;
        $min = 1;
        if(!empty($maxMessages)){
            $min = $maxMessages[0]['minOrder'];
            $max = $maxMessages[0]['maxOrder'];
        }
        $data = $this->SmsResponse->getColumnFormat($min, $max);
        $messages =  $this->SmsMessage->find('all', array(
        'conditions'=>array('enabled'=>1),
        'order'=>array('order'),
        'recursive'=>-1
        ));
        $fieldName = null;
        $result = null;
         $fieldName[] = '"' . __('Number') . '"';
        foreach($messages as $value){
            $fieldName[] = '"' . str_replace(',', ' ', $value['SmsMessage']['message']) . '"';
          
        }
        /*if(!empty($fieldName)){
             $fieldName[count($fieldName)-1] = end($fieldName) . "\n";
        }*/
        if(!empty($data)){
            $i = 0;
            foreach($data as $obj){
                foreach($obj as $key=>$value){
                    if(isset($value['number'])){
                        $result[$i][] = $value['number'];
                    }
                   $result[$i][] = '"' . str_replace(',', ' ',array_pop(array_values($value))) . '"';
                }
                $i++;
            }
        }
        echo $this->download_csv_results( 'sms_responses_' . date('Ymdhis') . '.csv');
        //pr(implode($fieldName, ','));
        //exit;
        echo $this->array2csv($result, $fieldName);
        die();
    }

     public function logsDownload($selectedType=null){
        $this->autoRender = false;
       
        $conditions = array();
        if(!empty($selectedType)){
            $conditions['send_receive'] = $selectedType;
        }
  
        $data = $this->SmsLog->find('all', array('fields'=>array('created', 'number', 'message', 'send_receive'), 'order'=>array('SmsLog.id DESC'), 'conditions'=>$conditions));

        $fieldName =  array('"' . __('Date/Time') . '"', '"' . __('Number') . '"','"' . __('Mesage') . '"','"' . __('Type') . '"');
        $result = null;
      
        if(!empty($data)){
            $i = 0;
            foreach($data as $obj){
                $obj = array_pop($obj);
                foreach($obj as $key=>$value){
                    if($key == 'send_receive'){
                        if($value == '1'){
                            $result[$i][] = __('Sent');
                        }else{
                            $result[$i][] = __('Recieved');
                        }
                    }else{
                         $result[$i][] = '"' . str_replace(',', ' ', $value) . '"';
                    }
                  
                }
                $i++;
            }
        }
        echo $this->download_csv_results( 'sms_logs_' . date('Ymdhis') . '.csv');
        //pr(implode($fieldName, ','));
        //exit;
        echo $this->array2csv($result, $fieldName);
        die();
    }
    
    
    public function responsesDelete() {
        $this->SmsResponse->truncate();
        $this->Utility->alert('All responses have been deleted successfully.');
        $this->redirect(array('action' => 'responses'));
    }

    function array2csv($results=NULL, $fieldName=NULL)
    {
       ob_end_clean();
       ob_start();
       $df = fopen("php://output", 'w');
       //fputs($df,$fieldName);
       fputs($df, implode(",", $fieldName)."\n");

        if(!empty($results)){
            foreach($results as $key=>$value){
                fputs($df, implode(",", $value)."\n");
            }
        }
       fclose($df);
       return ob_get_clean();
    }

    function download_csv_results($name = NULL)
    {
        if( ! $name)
        {
            $name = md5(uniqid() . microtime(TRUE) . mt_rand()). '.csv';
        }
        header('Expires: 0');
        header('Content-Encoding: UTF-8');
        // force download  
        header("Content-Type: application/force-download; charset=UTF-8'");
        header("Content-Type: application/octet-stream; charset=UTF-8'");
        header("Content-Type: application/download; charset=UTF-8'");
        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$name}");
        header("Content-Transfer-Encoding: binary");
    }
}
?>