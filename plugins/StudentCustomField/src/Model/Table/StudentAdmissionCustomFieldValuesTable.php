<?php
namespace StudentCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class StudentAdmissionCustomFieldValuesTable extends CustomFieldValuesTable
{
    protected $extra = ['scope' => 'student_custom_field_id'];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_custom_field_id']);
		//$this->belongsTo('CustomRecords', ['className' => 'User.Users', 'foreignKey' => 'student_id']);//not used
        //POCOR-8434 starts use for test purpose 
		$this->belongsTo('CustomRecords', [
			'foreignKey' => 'institution_student_admission_id',  // Match 'student_id' here as well
			'className' =>  'Institution.StudentAdmission'
		]);//POCOR-8434 ends
    }
}
