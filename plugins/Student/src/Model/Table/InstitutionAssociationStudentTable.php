<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class InstitutionAssociationStudentTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        //$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id', 'joinType' => 'INNER']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionAssociations', ['className' => 'Institution.InstitutionAssociations', 'foreignKey' => 'institution_association_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->hasMany('InstitutionStudents', ['className' => 'Institution.Students']);
        //$this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'AssociationStudent' => ['index','add','edit'],
        ]);
        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$session = $this->request->session();
		if ($this->controller->name == 'Profiles') {
			$sId = $session->read('Student.Students.id');
			if (!empty($sId)) {
				$studentId = $this->ControllerAction->paramsDecode($sId)['id'];
			} else {
				$studentId = $session->read('Auth.User.id');
			}
		} else {
				$studentId = $session->read('Student.Students.id');
		}
		
		// end POCOR-1893
		$sortList = ['AcademicPeriods.name'];
		
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        $query->where([$this->aliasField('security_user_id') => $studentId]);
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
	}

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields['security_user_id']['visible'] = false;
        $this->setFieldOrder('academic_period_id','name','education_grade_id','student_status_id');    
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }


    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_association_id') {
            return __('Name');
        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Associations');
    }

    
    public function getMaleCountByAssociations($associationId)
    {
        $gender_id = 1; // male
        $count = $this
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
            })
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_association_id') => $associationId])
            ->count()
        ;
        return $count;
    }

    public function getFemaleCountByAssociations($associationId)
    {
        $gender_id = 2; // female
        $count = $this
            ->find()
            ->contain('Users')
            ->matching('StudentStatuses', function ($q) {
                return $q->where(['StudentStatuses.code NOT IN' => ['TRANSFERRED', 'WITHDRAWN']]);
            })
            ->where([$this->Users->aliasField('gender_id') => $gender_id])
            ->where([$this->aliasField('institution_association_id') => $associationId])
            ->count()
        ;
        return $count;
    }

    // public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    // {
    //     if($entity->isNew() || $entity->dirty('student_status_id')) {
    //         $id = $entity->institution_association_id;
    //         $countMale = $this->getMaleCountByAssociations($id);
    //         $countFemale = $this->getFemaleCountByAssociations($id);
    //         $this->InstitutionAssociations->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    //     }
    // }

    // public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    // {   
    //     $id = $entity->institution_association_id;
    //     $countMale = $this->getMaleCountByAssociations($id);
    //     $countFemale = $this->getFemaleCountByAssociations($id);
    //     $this->InstitutionAssociations->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    // }
}
