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

class SmsResponse extends SmsAppModel {

	public function getColumnFormat($maxOrder = 1){
		$joins = array(
			array(
				'table' => 'sms_messages',
	            'alias' => 'SmsMessage',
	            'type' => 'INNER',
	            'conditions' => array(
	                'SmsResponse.order = SmsMessage.order',
	                'SmsMessage.enabled = 1'
	            )
	        )
        );
		
		$fields = array('SmsResponse.number', 'SmsResponse.response');
		for($i=2;$i<=$maxOrder;$i++){
			//$fields[] = 'SmsResponse'.$i.'.message';
			$fields[] = 'SmsResponse'.$i.'.response';
			$joins[] =
				array(
					'table' => 'sms_responses',
		            'alias' => 'SmsResponse'.$i,
		            'type' => 'INNER',
		            'conditions' => array(
		                'SmsResponse'.$i.'.order =' .$i
		            )
		        );
			
		}
		//pr($joins);
		$data = $this->find('all', array(
			'fields' => $fields,
		    'joins' => $joins,
			'conditions'=>array('SmsResponse.order'=>1),
			'order'=>array('SmsResponse.sent, SmsResponse.number')
	    ));

		return $data;
	}
	
}
