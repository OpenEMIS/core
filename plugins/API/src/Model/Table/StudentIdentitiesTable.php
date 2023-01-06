<?php
namespace API\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Exception;
use DateTime;

class StudentIdentitiesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_identities');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);

		$this->addBehavior('API.API');
	}

	public function search($conditions) {

		$data = $this->find('all')
				->join([
					'Student' => [
						'table' => 'security_users',
						'conditions' => [
							'Student.id = '.$this->aliasField('security_user_id'),
							'Student.is_student = 1'
						]
					],
					'IdentityType' => [
						'type' => 'LEFT',
						'table' => 'identity_types',
						'conditions' => [
							'IdentityType.id = '.$this->aliasField('identity_type_id')
						]
					],
					'InstitutionStudent' => [
						'type' => 'LEFT',
						'table' => 'institution_students',
						'conditions' => [
							'Student.id = InstitutionStudent.student_id'
						]
					],
					'Institution' => [
						'type' => 'LEFT',
						'table' => 'institutions',
						'conditions' => [
							'Institution.id = InstitutionStudent.institution_id'
						]
					],
					'AcademicPeriod' => [
						'type' => 'LEFT',
						'table' => 'academic_periods',
						'conditions' => [
							'AcademicPeriod.id = InstitutionStudent.academic_period_id'
						]
					],
					'EducationGrade' => [
						'type' => 'LEFT',
						'table' => 'education_grades',
						'conditions' => [
							'EducationGrade.id = InstitutionStudent.education_grade_id'
						]
					],
					'Statuses' => [
						'type' => 'LEFT',
						'table' => 'student_statuses',
						'conditions' => [
							'Statuses.id = InstitutionStudent.student_status_id'
						]
					]
				])
				->select([
					'openemis_id' => 'Student.openemis_no', 
					'Student.first_name', 'Student.middle_name', 'Student.third_name', 'Student.last_name',
					'InstitutionStudent.student_status_id',
					
					'Institution.name', 'Institution.code',
					'AcademicPeriod.name', 'AcademicPeriod.start_date', 'AcademicPeriod.end_date',
					'education_level' => 'EducationGrade.name',

					'identity_type' => 'IdentityType.name',
					$this->aliasField('number'),

					'status' => 'Statuses.name',
					'start_date' => 'InstitutionStudent.start_date',
					'end_date' => 'InstitutionStudent.end_date'
				])
				->order(['InstitutionStudent.created DESC'])
				->where($conditions)
				;

		if ($data->first()) {
			$data = $data->first();
			if ($data['InstitutionStudent']['student_status_id']=='1') {
				/**
				 * SS#: 000213123
				 * Name: Lina Marcela Tovar Velez
				 * Currently in school: Yes
				 * Current school: Pallotti High School
				 * Current school #: 251589
				 * Current level: Form 2
				 * OpenEMIS ID#: ????
				 */
				$result = [
					'id' => [
						'label' => $data['identity_type'] . ' #',
						'value' => $data['number'],
					],
					'name' => [
						'label' => 'Name',
						'value' => implode(' ', $data['Student']) ,
					],
					'status' => [
						'label' => 'Currently in school',
						'value' =>  $data['status'],
					],
					'school_name' => [
						'label' => 'Current school',
						'value' =>  $data['Institution']['name'],
					],
					'school_code' => [
						'label' => 'Current school #',
						'value' =>  $data['Institution']['code'],
					],
					'level' => [
						'label' => 'Current level',
						'value' =>  $data['education_level'],
					],
					'openemis_id' => [
						'label' => 'OpenEMIS ID#',
						'value' =>  $data['openemis_id'],
					],
					'error' => $this->getErrorMessage(0),
				];
			} else {
				/**
				 * SS#: 000213123
				 * Name: Mickey Mouse
				 * Currently in school: No
				 * Last known school: None
				 * Last known school #: 0
				 * Highest completed level: 0
				 * OpenEMIS ID#: ????
				 */
				// pr($data);
				$result = [
					'id' => [
						'label' => trim($data['identity_type']) . ' #',
						'value' => $data['number'],
					],
					'name' => [
						'label' => 'Name',
						'value' =>  implode(' ', $data['Student']) ,
					],
					'status' => [
						'label' => 'Last known status',
						'value' => $data['status'],
						'start_date' => date('d-M-Y', strtotime($data['start_date'])),
						'end_date' => date('d-M-Y', strtotime($data['end_date']))
					],
					'school_name' => [
						'label' => 'Last known school',
						'value' =>  ($data['Institution']['name']) ? $data['Institution']['name'] : 'NONE',
					],
					'school_code' => [
						'label' => 'Last known school #',
						'value' =>  ($data['Institution']['code']) ? $data['Institution']['code'] : 'NONE',
					],
					'level' => [
						'label' => 'Highest education level',
						'value' =>  ($data['education_level']) ? $data['education_level'] : 'NONE',
					],
					'openemis_id' => [
						'label' => 'OpenEMIS ID#',
						'value' =>  $data['openemis_id'],
					],
					'error' => $this->getErrorMessage(0),
				];
			}
		} else {
			/**
			 * no record
			 */
			$result = [];
		}

		return $result;
	}

}
