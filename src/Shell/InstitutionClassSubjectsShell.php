<?php
namespace App\Shell;

use ArrayObject;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\Exception\RecordNotFoundException;
use Report\Model\Table\ReportProgressTable as Process;

class InstitutionClassSubjectsShell extends Shell {
	public function initialize() {
		parent::initialize();
		
	}

 	public function main() {
		
		ini_set('memory_limit', '-1'); 

		$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');

		$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
		$data = $InstitutionClassSubjects
				->find()
				->select([
					$InstitutionClassSubjects->aliasField('institution_class_id')
				])
				->distinct([
					$InstitutionClassSubjects->aliasField('institution_class_id')
				])
				->toArray();
				
		$class_subjects = array();
		foreach ($data as $key => $value) {
			$class_subjects[$key] = $value->institution_class_id;
		}


		$InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
		$classesData = $InstitutionClasses->find()
				->where([$InstitutionClasses->aliasField('id').' NOT IN ' => $class_subjects])
				->toArray();
		
		foreach ($classesData as $key => $arr) {			
		$InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
         $educationGradesData = $InstitutionClassGrades
                                ->find()
                                ->where([
                                   $InstitutionClassGrades->aliasField('institution_class_id') => $arr->id
                                ])
                                ->toArray();
          
        if (!empty($educationGradesData)) {                      
        $grade = $educationGradesData[0]->education_grade_id;
        $grades = array($grade);
      
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            /**
             * from the list of grades, find the list of subjects group by grades in (education_grades_subjects) where visible = 1
             */
            $educationGradeSubjects = $EducationGrades
                    ->find()
                    ->contain(['EducationSubjects' => function ($query) use ($grades) {
                        return $query
                            ->join([
                                [
                                    'table' => 'education_grades_subjects',
                                    'alias' => 'GradesSubjects',
                                    'conditions' => [
                                        'GradesSubjects.education_grade_id IN' => $grades,
                                        'GradesSubjects.education_subject_id = EducationSubjects.id',
                                        'GradesSubjects.visible' => 1
                                    ]
                                ]
                            ]);
                    }])
                    ->where([
                        'EducationGrades.id IN' => $grades,
                        'EducationGrades.visible' => 1
                    ])
                    ->toArray();
            unset($EducationGrades);
            unset($grades);

            $educationSubjects = [];
            if (count($educationGradeSubjects) > 0) {
                foreach ($educationGradeSubjects as $gradeSubject) {
                    foreach ($gradeSubject->education_subjects as $subject) {
                        if (!isset($educationSubjects[$gradeSubject->id.'_'.$subject->id])) {
                            $educationSubjects[$gradeSubject->id.'_'.$subject->id] = [
                                'id' => $subject->id,
                                'education_grade_id' => $gradeSubject->id,
                                'name' => $subject->name
                            ];
                        }
                    }
                    unset($subject);
                }
                unset($gradeSubject);
            }
            unset($educationGradeSubjects);

            if (!empty($educationSubjects)) {
                /**
                 * for each education subjects, find the primary key of institution_classes using (entity->academic_period_id and institution_id and education_subject_id)
                 */
                $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
                $institutionSubjects = $InstitutionSubjects->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'education_subject_id'
                    ])
                    ->where([
                        $InstitutionSubjects->aliasField('academic_period_id') => $arr->academic_period_id,
                        $InstitutionSubjects->aliasField('institution_id') => $arr->institution_id,
                        $InstitutionSubjects->aliasField('education_subject_id').' IN' => array_column($educationSubjects, 'id')
                    ])
                    ->toArray();
                $institutionSubjectsIds = [];
                foreach ($institutionSubjects as $key => $value) {
                    $institutionSubjectsIds[$value][] = $key;
                }

                unset($institutionSubjects);

                /**
                 * using the list of primary keys, search institution_class_subjects (InstitutionClassSubjects) to check for existing records
                 * if found, don't insert,
                 * else create a record in institution_subjects (InstitutionSubjects)
                 * and link to the subject in institution_class_subjects (InstitutionClassSubjects) with status 1
                 */
                $InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
                $newSchoolSubjects = [];
                foreach ($educationSubjects as $key => $educationSubject) {                 
                        $existingSchoolSubjects = $InstitutionClassSubjects->find()
                            ->where([
                                $InstitutionClassSubjects->aliasField('institution_class_id') => $arr->id
                            ])
                            ->toArray();   
                    
                    if (empty($existingSchoolSubjects)) {                        
                        $newSchoolSubjects[$key] = [
                            'name' => $educationSubject['name'],
                            'institution_id' => $arr->institution_id,
                            'education_grade_id' => $educationSubject['education_grade_id'],
                            'education_subject_id' => $educationSubject['id'],
                            'academic_period_id' => $arr->academic_period_id,
                            'class_subjects' => [
                                [
                                    'status' => 1,
                                    'institution_class_id' => $arr->id
                                ]
                            ]
                        ];
                    }
                }
                
                if (!empty($newSchoolSubjects)) {
                    $newSchoolSubjects = $InstitutionSubjects->newEntities($newSchoolSubjects);
                    foreach ($newSchoolSubjects as $subject) {
                       
                        //POCOR 5001
                        $institutionProgramGradeSubjects = 
                            TableRegistry::get('InstitutionProgramGradeSubjects')
                            ->find('list')
                            ->where(['InstitutionProgramGradeSubjects.education_grade_id' => $subject->education_grade_id,
                                'InstitutionProgramGradeSubjects.education_grade_subject_id' => $subject->education_subject_id,
                                'InstitutionProgramGradeSubjects.institution_id' => $subject->institution_id
                                ])
                            ->count(); 
                        
                        if($institutionProgramGradeSubjects > 0){

                            $InstitutionSubjects->save($subject);
                        }
                    }
                    unset($subject);
                }
                unset($newSchoolSubjects);
                unset($InstitutionSubjects);
                unset($InstitutionClassSubjects);
            }
            }
        }	
	}	
}
