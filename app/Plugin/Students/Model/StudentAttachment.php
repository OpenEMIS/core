<?php
// App::uses('StudentsAppModel', 'Model');

class StudentAttachment extends StudentsAppModel {
    public $belongsTo = array('Student');

    public $virtualFields = array(
        'blobsize' => "OCTET_LENGTH(file_content)"
    );

}
?>