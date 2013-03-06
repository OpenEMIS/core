<?php
// App::uses('StaffsAppModel', 'Model');

class StaffAttachment extends StaffAppModel {
    public $belongsTo = array('Staff');

    public $virtualFields = array(
        'blobsize' => "OCTET_LENGTH(file_content)"
    );
}
?>