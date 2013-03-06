<?php
// App::uses('TeachersAppModel', 'Model');

class TeacherAttachment extends TeachersAppModel {
    public $belongsTo = array('Teacher');

    public $virtualFields = array(
        'blobsize' => "OCTET_LENGTH(file_content)"
    );
}
?>