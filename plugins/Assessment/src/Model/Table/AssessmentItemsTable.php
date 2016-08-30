<?php
namespace Assessment\Model\Table;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Query;

use App\Model\Table\AppTable;

class AssessmentItemsTable extends AppTable {

	public function initialize(array $config) 
	{
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);

	}

	public function validationDefault(Validator $validator) 
	{
		$validator = parent::validationDefault($validator);

		$validator
			->add('weight', 'ruleIsDecimal', [
				'rule' => ['decimal', null],
			])
			->add('weight', 'ruleWeightRange', [
                'rule'  => ['range', 0, 2],
                'last' => true
            ]);
		return $validator;
	}

	public function populateAssessmentItemsArray($gradeId) 
	{
		$EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
		$gradeSubjects = $EducationGradesSubjects->find()
			->contain('EducationSubjects')
			->where([$EducationGradesSubjects->aliasField('education_grade_id') => $gradeId])
			->order(['order'])
			->toArray();

		$assessmentItems = [];
		foreach ($gradeSubjects as $key => $gradeSubject) {
			if (!empty($gradeSubject->education_subject)) {
				$assessmentItems[] = [
				    'education_subject_id' => $gradeSubject->education_subject->id,
				    'education_subject' => $gradeSubject->education_subject,
					'weight' => '0.00'
				];
			}
		}
		return $assessmentItems;
	}

	public function getAssessmentItemSubjects($assessmentId) 
	{
		$subjectList = $this
			->find()
			->matching('EducationSubjects')
			->where([$this->aliasField('assessment_id') => $assessmentId])
			->select([
				'assessment_item_id' => $this->aliasField('id'),
				'education_subject_id' => 'EducationSubjects.id',
				'education_subject_name' => $this->find()->func()->concat([
					'EducationSubjects.code' => 'literal',
					" - ",
					'EducationSubjects.name' => 'literal'
				])
			])
			->order(['EducationSubjects.order'])
			->hydrate(false)
			->toArray();
		return $subjectList;
	}

	public function afterDelete()
	{
		// delete all AssessmentItemsGradingTypes by education_subject_id and assessment_id
	}
}