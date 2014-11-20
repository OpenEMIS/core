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
        'ConfigItem',
		'Option',
		'Alerts.AlertLog'
    );


    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('receive');
        $this->bodyTitle = 'Administration';
        $this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('SMS', array('controller' => $this->name, 'action' => 'messages'));
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

    public function testsend() {
		$this->autoRender = false;
		$providerUrl = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_provider_url'));
        $smsNumberField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_number'));
        $smsContentField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_content'));

		$param = array($smsNumberField => '962799360680', $smsContentField => 'this is a test message');
        $HttpSocket = new HttpSocket();
        //$results = $HttpSocket->get($providerUrl, $param);
		$msg = $this->SmsLog->findById(26);
		//pr($msg);
		$str = $msg['SmsLog']['message'];//mb_convert_encoding('ما هو اسمك؟', 'UCS-2');
		$result = $HttpSocket->get($providerUrl . '&M=%2B962799360680&B='.$str);

		$this->log('test send', 'sms');
		$this->log($providerUrl, 'sms');
		$this->log($HttpSocket->response, 'sms');
		$this->log('test send end', 'sms');
    }

    public function sent($provider, $number, $followingMessage){
        $providerUrl = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_provider_url'));
        $smsNumberField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_number'));
        $smsContentField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_content'));
    
        $param = array($smsNumberField => $number, $smsContentField => $followingMessage['message']);
        $HttpSocket = new HttpSocket();
        $data = array();
        $logData = array();
        $this->log($providerUrl, 'sms');
        $this->log($param, 'sms');
		$responseOK = false;
        switch ($provider) {
            case "smsdome":
				$results = $HttpSocket->post($providerUrl, $param);
                $response = json_decode($HttpSocket->response, true);
				$responseOK = $response['result']['status'] == "OK";
                break;
			case "arabiacell":
				$results = $HttpSocket->get($providerUrl, $param);
				$responseOK = $HttpSocket->response['body'] == "success";
                break;
            default:
                echo "Incorrect provider";
                break;
        }
		$this->log($HttpSocket->response, 'sms');
		if($responseOK) {
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
			$this->SmsResponse->saveAll($data);
			$this->SmsLog->saveAll($logData);
			echo 1;
		} else {
			echo 0;
		}
    }

    public function index(){
        return $this->redirect(array('action'=>'messages'));
    }
    public function messages() {
        $this->Navigation->addCrumb(__('Messages'));

        $data = $this->SmsMessage->find('all', array('order'=>array('SmsMessage.order ASC')));
        $this->set('data', $data);
    }

    public function messagesAdd(){
        $this->Navigation->addCrumb(__('Add Message'));
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
            $this->Navigation->addCrumb(__('Message Details'));
            
            $this->Session->write('SmsMessageId', $id);
            $this->set('obj', $obj);
        } 
    }

    public function messagesEdit() {
        $id = $this->params['pass'][0];
        if($this->request->is('get')) {
            $obj = $this->SmsMessage->find('first',array('conditions'=>array('SmsMessage.id' => $id)));
  
            if(!empty($obj)) {
                $this->Navigation->addCrumb(__('Edit Message Details'));
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
            $this->Utility->alert($name . __(' have been deleted successfully.'));
            $this->redirect(array('action' => 'messages'));
        }
    }

     public function logs() {
        $this->Navigation->addCrumb(__('Logs'));

		if ($this->request->is(array('post', 'put'))) {
			$formData = $this->request->data;
			if ($formData['submit'] == 'reload') {
				$criterials = $formData['Log'];
				$type = $criterials['type'];
				$method = $criterials['method'];
				$channel = $criterials['channel'];
				$status = $criterials['status'];
				
				$data = array();
				if($method == 'Email'){
					$data = $this->AlertLog->getLogs($status);
				}else if($method == 'SMS'){
					
				}
			}
		}
		
//        $conditions = array();
//        if(!empty($selectedType)){
//            $conditions['send_receive'] = $selectedType;
//        }
//
//        $data = $this->SmsLog->find('all', array('order'=>array('SmsLog.id DESC'), 'conditions'=>$conditions));
		//$data = array();
        $this->set('data', $data);

        //$typeOptions = array('1'=>__('Sent'), '2'=>__('Received'));
		
		$typeOptions = $this->Option->get('alertType');
		$methodOptions = $this->Option->get('alertMethod');
		$channelOptions = $this->Option->get('alertChannel');
		$statusOptions = $this->Option->get('alertStatus');
		
        //$this->set('typeOptions', $typeOptions);
        //$this->set('selectedType', $selectedType);
		$this->set(compact('typeOptions', 'channelOptions', 'selectedType', 'statusOptions', 'methodOptions'));
    }

    
    public function logsDelete() {
        $this->SmsLog->truncate();
        $this->Utility->alert(__('All logs have been deleted successfully.'));
        $this->redirect(array('action' => 'logs'));
        
    }

    public function responses() {
        $this->Navigation->addCrumb(__('Responses'));

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
        $this->Utility->alert(__('All responses have been deleted successfully.'));
        $this->redirect(array('action' => 'responses'));
    }


    public function reports(){
        $this->Navigation->addCrumb('Reports');
        $data = array('Reports' => array(
                array('name' => 'SMS Report', 'types' => array('XLSX'))
        ));
        $this->set('data', $data);
    }
    public function genReport($name, $type) { //$this->genReport('Site Details','CSV');
        $this->autoRender = false;

        if (method_exists($this, 'gen' . $type)) {
            if ($type == 'XLSX') {
                $data = $this->getReportData($name);
                $this->genXLSX($data, $name);
            }
        }
    }

    public function getReportData($name){
        $data = array();
        switch ($name) {
            case 'SMS Report':
            $data = $this->SmsResponse->query("SELECT count(distinct number) as Number, 'NumberOfPaticipants' as countType, 1 as countId
                    FROM `sms_responses` AS `SmsResponse`
                    UNION
                    SELECT sum(response) as Number, 'NumberOfSyrians' as countType, 2 as countId
                    FROM `sms_responses` AS `SmsResponse` where `order`= '2' and response = '1'
                    UNION
                    SELECT count(distinct number) as Number, 'NumberOfCompleted' as countType, 3 as countId
                    FROM `sms_responses` AS `SmsResponse` where `order`= '6'
                    UNION
                    SELECT count(Number), countType, countId FROM(
                    SELECT count(distinct number) as Number, 'NumberOfPartial' as countType, 4 as countId
                    FROM `sms_responses` AS `SmsResponse`
                    group by number
                    having max(`order`) < 6
                    )a
                    UNION
                    SELECT sum(response) as Number, 'NumberOfAbove16' as countType, 5 as countId
                    FROM `sms_responses` AS `SmsResponse` where `order`= '3'
                    UNION
                    SELECT sum(response) as Number, 'NumberOfBetween6And16' as countType, 6 as countId 
                    FROM `sms_responses` AS `SmsResponse` where `order`= '4'
                    UNION
                    SELECT sum(response) as Number, 'NumberOfBetween6And16School' as countType, 7 as countId 
                    FROM `sms_responses` AS `SmsResponse` where `order`= '5'
                    UNION
                    SELECT sum(SmsResponse1.response)-sum(SmsResponse2.response) as Number, 'NumberOfBetween6And16NoSchool' as countType, 9 as countId 
                    FROM `sms_responses` AS `SmsResponse1`
                    LEFT JOIN `sms_responses` AS `SmsResponse2` on `SmsResponse2`.`order`= '5' and `SmsResponse2`.`number` = `SmsResponse1`.`number`
                     where `SmsResponse1`.`order`= '4'");
                break;
        }
        return $data;

    }

    public function genXLSX($data, $name){
        $webroot = WWW_ROOT;
        $view = new View($this);
        $phpExcel = $view->loadHelper('PhpExcel');
        switch ($name) {
            case 'SMS Report':
                $templatePath = $webroot . 'reports/Sms_Reports/Sms/sms_report_template.xlsx';
                if (file_exists($templatePath)) {
                     $phpExcel->loadWorksheet($templatePath);
                     $phpExcel->setDefaultFont('Calibri', 12);
                } 


                $i = 4;
                foreach($data as $key=>$val){
                    $phpExcel->changeCell($val[0]['Number'],'B'.$i); 
                    $i++;
                }
               
                $phpExcel->createSmsPieChart();

                $phpExcel->output('sms_report_' . date('Ymdhis') . '.xlsx'); 
                break;
        }
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
