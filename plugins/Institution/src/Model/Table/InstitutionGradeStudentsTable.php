<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class InstitutionGradeStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey'=>'security_user_id']);

		// $this->belongsToMany('InstitutionSiteClasses', [
		// 	'className' => 'Institution.InstitutionSiteClasses',
		// 	'joinTable' => 'institution_site_section_classes',
		// 	'foreignKey' => 'institution_site_section_id',
		// 	'targetForeignKey' => 'institution_site_class_id'
		// ]);

		$this->Sections = $this->Institutions->InstitutionSiteSections;
		$this->SectionStudents = $this->Institutions->InstitutionSiteSections->InstitutionSiteSectionStudents;
	}

	public function allAssociations() {
		return ['Institutions', 'Students', 'AcademicPeriods', 'EducationGrades'];
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

	public function beforeAction(Event $event) {}

	public function beforeFind(Event $event, Query $query, $options) {

		// die('beforeFind');

	// 	$query
	// 		->join([
	// 			'table' => 'student_guardians',
	// 			'alias' => 'GuardianStudents',
	// 			'type' => 'INNER',
	// 			'conditions' => [$this->_table->aliasField('id').' = '. 'GuardianStudents.guardian_user_id']
	// 		])
	// 		->group($this->_table->aliasField('id'));
	}

    public function findByGrades(Query $query, array $options) {
    	$this->syncRecords($options); 
    	// pr($options);die;
    	return $query->where([
	    		$this->aliasField('institution_id') => $options['institution_id'],
	    		$this->aliasField('education_grade_id') => $options['education_grade_id'],
	    		$this->aliasField('academic_period_id') => $options['academic_period_id']
    		])
			// ->join([
			// 	[
			// 		'table' => 'institution_site_fees',
			// 		'alias' => 'InstitutionSiteFees',
			// 		'type' => 'left',
			// 		'conditions' => [
			// 			'InstitutionSiteFees.education_grade_id' => $options['education_grade_id'],
			// 			'InstitutionSiteFees.academic_period_id' => $options['academic_period_id'],
			// 			'InstitutionSiteFees.institution_site_id' => $options['institution_id']
			// 		]
			// 	]
			// ])
			;
    }

    private function syncRecords($options) {
    	$self = $this->find();
    	$gradeStudents = $self->where([
	    		$this->aliasField('institution_id') => $options['institution_id'],
	    		$this->aliasField('education_grade_id') => $options['education_grade_id'],
	    		$this->aliasField('academic_period_id') => $options['academic_period_id']
    		])
			->select([
				'total' => $self->func()->count($this->aliasField('security_user_id'))
			])
			->first()
			;
		// pr($gradeStudents);die;

    	$sectionQuery = $this->Sections->find('list');
    	$sections = array_keys($sectionQuery->where([
	    		$this->Sections->aliasField('institution_site_id') => $options['institution_id'],
	    		$this->Sections->aliasField('academic_period_id') => $options['academic_period_id']
    		])
			->select(['id'])
			->toArray())
			;
		// pr($sections);die;

    	$sectionStudentQuery = $this->SectionStudents->find();
    	$sectionStudents = $sectionStudentQuery->where([
	    		$this->SectionStudents->aliasField('education_grade_id') => $options['education_grade_id'],
	    		$this->SectionStudents->aliasField('institution_site_section_id IN') => $sections
    		])
			->select([
				'total' => $sectionStudentQuery->func()->count($this->SectionStudents->aliasField('security_user_id'))
			])
			->first()
			;

		// pr('gradeStudents: '. $gradeStudents->total .'<br/>sectionStudents: '. $sectionStudents->total);die;

		if ($gradeStudents->total != $sectionStudents->total) {
			$this->deleteAll([
				'institution_id' => $options['institution_id'],
				'education_grade_id' => $options['education_grade_id'],
	    		'academic_period_id'=> $options['academic_period_id']
			]);

	    	$sectionStudentQuery = $this->SectionStudents->find();
 		   	$sectionStudents = $sectionStudentQuery->where([
	    		$this->SectionStudents->aliasField('education_grade_id') => $options['education_grade_id'],
	    		$this->SectionStudents->aliasField('institution_site_section_id IN') => $sections
    		])
			->select([
				$this->SectionStudents->aliasField('security_user_id')
			])
			->toArray()
			;
			$data = [];
			foreach ($sectionStudents as $key=>$value) {
				$data[] = [
					'institution_id'=>$options['institution_id'],
					'security_user_id'=>$value->security_user_id,
					'education_grade_id'=>$options['education_grade_id'],
		    		'academic_period_id'=> $options['academic_period_id']
				];
			}
			$newEntities = $this->newEntities($data);
			foreach ($newEntities as $entity) {
		    	$this->save($entity);
			}

		}

    }

}
