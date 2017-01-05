<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Utility\Text;

class InstitutionSubjectStudentsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);

		$this->belongsTo('ClassStudents', [
			'className' => 'Institution.InstitutionClassStudents',
			'foreignKey' => [
				'institution_class_id',
				'student_id'
			],
			'bindingKey' => [
				'institution_class_id',
				'student_id'
			]
		]);

		$this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'add']
        ]);
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->isNew()) {
			$entity->id = Text::uuid();
		}
	}

	public function findResults(Query $query, array $options) {
		$institutionId = $options['institution_id'];
		$classId = $options['class_id'];
		$assessmentId = $options['assessment_id'];
		$periodId = $options['academic_period_id'];
		$subjectId = $options['subject_id'];

		$Users = $this->Users;
		$InstitutionSubjects = $this->InstitutionSubjects;
        $StudentStatuses = $this->StudentStatuses;
		$ItemResults = TableRegistry::get('Assessment.AssessmentItemResults');

		return $query
			->select([
				$ItemResults->aliasField('id'),
				$ItemResults->aliasField('marks'),
				$ItemResults->aliasField('assessment_grading_option_id'),
				$ItemResults->aliasField('assessment_period_id'),
				$this->aliasField('student_id'),
                $this->aliasField('student_status_id'),
				$this->aliasField('total_mark'),
				$Users->aliasField('openemis_no'),
				$Users->aliasField('first_name'),
				$Users->aliasField('middle_name'),
				$Users->aliasField('third_name'),
				$Users->aliasField('last_name'),
				$Users->aliasField('preferred_name'),
                $StudentStatuses->aliasField('code'),
                $StudentStatuses->aliasField('name')
			])
			->matching('Users')
            ->contain('StudentStatuses')
			->innerJoin(
				[$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
				[
					$InstitutionSubjects->aliasField('id = ') . $this->aliasField('institution_subject_id'),
					$InstitutionSubjects->aliasField('institution_id') => $institutionId,
					$InstitutionSubjects->aliasField('academic_period_id') => $periodId,
					$InstitutionSubjects->aliasField('education_subject_id') => $subjectId
				]
			)
			->leftJoin(
				[$ItemResults->alias() => $ItemResults->table()],
				[
					$ItemResults->aliasField('student_id = ') . $this->aliasField('student_id'),
					$ItemResults->aliasField('assessment_id') => $assessmentId,
					$ItemResults->aliasField('institution_id') => $institutionId,
					$ItemResults->aliasField('academic_period_id') => $periodId,
					$ItemResults->aliasField('education_subject_id') => $subjectId
				]
			)
			->where([
				$this->aliasField('institution_class_id') => $classId
			])
			->group([
				$this->aliasField('student_id'),
				$ItemResults->aliasField('assessment_period_id')
			])
			->order([
				$this->aliasField('student_id')
			]);
	}

	public function getMaleCountBySubject($subjectId) {
		$gender_id = 1; // male
		$count = $this
			->find()
			->contain('Users')
			->where([$this->Users->aliasField('gender_id') => $gender_id])
			->where([$this->aliasField('institution_subject_id') => $subjectId])
			->count()
		;
		return $count;
	}

	public function getFemaleCountBySubject($subjectId) {
		$gender_id = 2; // female
		$count = $this
			->find()
			->contain('Users')
			->where([$this->Users->aliasField('gender_id') => $gender_id])
			->where([$this->aliasField('institution_subject_id') => $subjectId])
			->count()
		;
		return $count;
	}

	public function afterDelete(Event $event, Entity $entity, ArrayObject $options) {
		//PHPOE-2338 - implement afterDelete to delete records in AssessmentItemResultsTable
		// find related classes and grades
		$InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
		$institutionClassData = $InstitutionSubjects->find()
			->contain('Classes.ClassGrades')
			->where([$InstitutionSubjects->aliasField($InstitutionSubjects->primaryKey()) => $entity->institution_subject_id])
			->first()
			;
		$gradeArray = [];
		if (!empty($institutionClassData->institution_classes)) {
			foreach ($institutionClassData->institution_classes as $skey => $svalue) {
				if (!empty($svalue->institution_class_grades)) {
					foreach ($svalue->institution_class_grades as $gkey => $gvalue) {
						$gradeArray[] = $gvalue->education_grade_id;
					}
				}
			}
		}
		$gradeArray = array_unique($gradeArray);

		$AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
		// conditions: 'assessment_item_results' removing from student_id, institution_id, academic_period_id, assessment_item_id->education_subject_id;
		$deleteAssessmentItemResults = $AssessmentItemResults->find()
			->where([
				$AssessmentItemResults->aliasField('student_id') => $entity->student_id,
				$AssessmentItemResults->aliasField('institution_id') => $institutionClassData->institution_id,
				$AssessmentItemResults->aliasField('academic_period_id') => $institutionClassData->academic_period_id,

			])
			;

		if (!empty($gradeArray)) {
			$deleteAssessmentItemResults->matching('Assessments', function ($q) use ($gradeArray) {
			    return $q->where(['Assessments.education_grade_id IN ' => $gradeArray]);
			})
			;
		}

		foreach ($deleteAssessmentItemResults as $key => $value) {
			$AssessmentItemResults->delete($value);
		}
	}

    public function getEnrolledStudentBySubject($period, $class, $subject)
    {
    	$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$enrolled = $StudentStatuses->getIdByCode('CURRENT');

        $Users = $this->Users;

        $students = $this
                    ->find()
                    ->matching('Users')
                    ->matching('ClassStudents', function($q) use ($enrolled) {
                        return $q->where([
                        	'ClassStudents.student_status_id' => $enrolled
                        ]);
                    })
                    ->where([
                        $this->aliasField('academic_period_id') => $period,
                        $this->aliasField('institution_class_id') => $class,
                        $this->aliasField('education_subject_id') => $subject
                    ])
                    ->select([
                        $this->aliasField('student_id'),
                        $Users->aliasField('openemis_no'),
                        $Users->aliasField('first_name'),
                        $Users->aliasField('middle_name'),
                        $Users->aliasField('third_name'),
                        $Users->aliasField('last_name'),
                        $Users->aliasField('preferred_name')
                    ])->toArray();
        
        $studentList = [];
        foreach ($students as $key => $value) {
            $studentList[$value->student_id] = $value->_matchingData['Users']['name_with_id'];
        }

        return $studentList;
    }

    public function getStudentClassGradeDetails($period, $institution, $student, $subject) //function return class and grade of student.
    {
        return  $this
                ->find()
                ->innerJoin(
                    ['InstitutionClassGrades' => 'institution_class_grades'],
                    [
                        'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('institution_class_id'),
                    ]
                )
                ->where([
                    $this->aliasField('student_id') => $student,
                    $this->aliasField('institution_id') => $institution,
                    $this->aliasField('academic_period_id') => $period,
                    $this->aliasField('education_subject_id') => $subject
                ])
                ->select([
                    $this->aliasField('institution_class_id'),
                    'education_grade_id' => 'InstitutionClassGrades.education_grade_id'
                ])
                ->toArray();
    }
}
