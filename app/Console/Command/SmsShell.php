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

class SmsShell extends AppShell {
    public $uses = array(
        'SmsMessage',
        'SmsResponse',
        'SmsLog',
        'ConfigItem'
    );
    
    public function main() {}
	
    public function _welcome() {}
	
    public function run($provider) {
        $this->out('Started test - ' . date('Y-m-d H:i:s'));
        $this->out($provider);
        $providerUrl = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_provider_url'));
        $smsNumberField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_number'));
        $smsContentField = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_content'));
    
        $retryTimes = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_retry_times'));
        $retryWait = $this->ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'sms_retry_wait'));
        $retryResponses = $this->SmsResponse->find('all', array(
            'conditions'=>array(
                'SmsResponse.sent_count <' => $retryTimes, 
                'SmsResponse.received'=>null, 
                'SmsResponse.sent <=' =>date('Y-m-d H:i:s', strtotime("-" . $retryWait . " seconds"))
                )
            )
        );
        $data = array();
        $logData = array();

        if(!empty($retryResponses)){
            foreach($retryResponses as $obj){
               $this->sent($provider, $obj);
            }
        }
        $this->out('Ended - ' . date('Y-m-d H:i:s'));
    }


    public function sent($provider, $obj){
        $param = array($smsNumberField => $obj['SmsResponse']['number'], $smsContentField => $obj['SmsResponse']['message']);
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
                            'id' => $obj['SmsResponse']['id'],
                            'sent' => date('Y-m-d H:i:s'),
                            'sent_count' => $obj['SmsResponse']['sent_count']+1
                        )
                    );

                    $logData = array(
                        'SmsLog' => array(
                            'send_receive' => 1,
                            'created' => date('Y-m-d H:i:s'),
                            'number' => $obj['SmsResponse']['number'],
                            'message' => $obj['SmsResponse']['message']
                        )
                    );
                    $this->out(1);
                }else{
                    $this->out(0);
                    $this->log($response, 'sms');
                }
                break;
            default:
                echo "Incorrect provider";
                break;
        }


        if(!empty($data) && !empty($logData)){
            $this->out($provider);
            $this->out('Sent test     ' . count($data) . ' SMS');
            $this->SmsResponse->saveAll($data);
            $this->SmsLog->saveAll($logData);
        }
    }
}

?>
