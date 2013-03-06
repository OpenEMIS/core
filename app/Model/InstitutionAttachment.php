<?php
App::uses('AppModel', 'Model');

class InstitutionAttachment extends AppModel {
       public $belongsTo = array('Institution');
       
       public $virtualFields = array(
		'blobsize' => "OCTET_LENGTH(file_content)"
	);
}
?>