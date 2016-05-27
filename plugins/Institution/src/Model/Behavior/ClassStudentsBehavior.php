<?php 
namespace Institution\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\I18n\Date;

class ClassStudentsBehavior extends Behavior {
	public function findStudentClasses(Query $query, array $options) {
		$model = $this->_table;
		$query
			->leftJoin(['InstitutionClassStudents' => 'institution_class_students'],
				[	
					'InstitutionClassStudents.education_grade_id = '.$model->aliasField('education_grade_id'),
					'InstitutionClassStudents.student_id = '.$model->aliasField('student_id'),
					'InstitutionClassStudents.institution_id = '.$model->aliasField('institution_id'),
					'InstitutionClassStudents.academic_period_id = '.$model->aliasField('academic_period_id'),
				]);

		if (array_key_exists('institution_class_id', $options)) {
			if (!empty($options['institution_class_id'])) {
				if ($options['institution_class_id'] != -1) {
					$query->where(['InstitutionClassStudents.institution_class_id' => $options['institution_class_id']]);
				} else {
					$query->where(['InstitutionClassStudents.institution_class_id IS NULL']);
				}
			} else {
				$query->where(['1 = 0']);
			}
		}
		return $query;
	}
}
