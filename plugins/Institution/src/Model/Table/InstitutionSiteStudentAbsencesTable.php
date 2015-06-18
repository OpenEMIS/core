<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InstitutionSiteStudentAbsencesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Institution.Absence');
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'security_user_id']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('StudentAbsenceReasons', ['className' => 'FieldOption.StudentAbsenceReasons']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function getStudentAbsenceDataByMonth($studentId, $academicPeriodId, $monthId){
		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$academicPeriod = $AcademicPeriod->getAcademicPeriodById($academicPeriodId);
		
		$conditions = array(
			'Student.id = ' . $studentId,
		);
		
		$conditions['OR'] = array(
			array(
				'MONTH(InstitutionSiteStudentAbsence.first_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStudentAbsence.first_date_absent) = "' . $academicPeriod . '"'
			),
			array(
				'MONTH(InstitutionSiteStudentAbsence.last_date_absent) = "' . $monthId . '"',
				'YEAR(InstitutionSiteStudentAbsence.last_date_absent) = "' . $academicPeriod . '"'
			)
		);
		
		$data = $this->find('all', array(
			'fields' => array(
				'DISTINCT InstitutionSiteStudentAbsence.id', 
				'InstitutionSiteStudentAbsence.absence_type', 
				'InstitutionSiteStudentAbsence.first_date_absent', 
				'InstitutionSiteStudentAbsence.last_date_absent', 
				'InstitutionSiteStudentAbsence.full_day_absent', 
				'InstitutionSiteStudentAbsence.start_time_absent', 
				'InstitutionSiteStudentAbsence.end_time_absent',
				'StudentAbsenceReason.name'
			),
			'conditions' => $conditions,
			'order' => array('InstitutionSiteStudentAbsence.first_date_absent', 'InstitutionSiteStudentAbsence.last_date_absent')
		));
		
		return $data;
	}


}
