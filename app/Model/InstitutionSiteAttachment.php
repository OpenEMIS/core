<?php
App::uses('AppModel', 'Model');

class InstitutionSiteAttachment extends AppModel {
       public $belongsTo = array('InstitutionSite' => 
       array('foreignKey' => 'institution_site_id')
       );
       
       public $virtualFields = array(
		'blobsize' => "OCTET_LENGTH(file_content)"
	);
}
?>