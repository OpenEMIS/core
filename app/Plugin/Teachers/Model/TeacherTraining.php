<?php
App::uses('UtilityComponent', 'Component');

class TeacherTraining extends TeachersAppModel {
	public $useTable = "teacher_training";
	
	public function getData($id) {

        $utility = new UtilityComponent(new ComponentCollection);
		$options['joins'] = array(
            array('table' => 'teacher_training_categories',
            	'alias' => 'TeacherTrainingCategories',
                'type' => 'LEFT',
                'conditions' => array(
                    'TeacherTrainingCategories.id = TeacherTraining.teacher_training_category_id'
                )
            )
        );

        $options['fields'] = array(
        	'TeacherTraining.id',
            'TeacherTraining.teacher_id',
            'TeacherTraining.teacher_training_category_id',
        	'TeacherTrainingCategories.name',
        	'TeacherTraining.completed_date'
        );

        $options['conditions'] = array('TeacherTraining.teacher_id' => $id);

        $options['order'] = array('TeacherTraining.completed_date DESC');

		$list = $this->find('all', $options);
		$list = $utility->formatResult($list);

		return $list;
	}
}