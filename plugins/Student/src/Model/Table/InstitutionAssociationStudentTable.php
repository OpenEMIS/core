<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\ORM\ResultSet;
use Cake\Http\ServerRequest;

use App\Model\Table\ControllerActionTable;

class InstitutionAssociationStudentTable extends ControllerActionTable
{
    public function initialize(array $config): void
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

        $this->addBehavior('Institution.InstitutionTab');
    }
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
	{
		$session = $this->request->getSession();
		if ($this->controller->getName() == 'Profiles') {
            if ($session->read('Auth.User.is_guardian') == 1) {
                $sId = $session->read('Student.ExaminationResults.student_id');
            } else {
                 $sId = $this->getUserID();
            }
			if (!empty($sId)) {
				$studentId = $this->ControllerAction->paramsDecode($sId)['id'];
			} else {
				$studentId = $session->read('Auth.User.id');
			}
		} else {
				$studentId = $this->getStudentID();
		}
		
		// end POCOR-1893
		$sortList = ['AcademicPeriods.name'];
		
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        $query->where([$this->aliasField('security_user_id IS') => $studentId]);
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
	}

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['security_user_id']['visible'] = false;
        $this->setFieldOrder('academic_period_id','name','education_grade_id','student_status_id');  

        // Start POCOR-5188
        $toolbarButtons = $extra['toolbarButtons'];
        $is_manual_exist = $this->getManualUrl('Institutions','Associations','Students - Academic');       
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];

            $toolbarButtons['help']['url'] = $is_manual_exist['url'];
            $toolbarButtons['help']['type'] = 'button';
            $toolbarButtons['help']['label'] = '<i class="fa fa-question-circle"></i>';
            $toolbarButtons['help']['attr'] = $btnAttr;
            $toolbarButtons['help']['attr']['title'] = __('Help');
        }
        // End POCOR-5188
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }


    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_association_id') {
            return __('Name');
        } elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'education_grade_id') {
            return __('Education Grade');
        } elseif ($field == 'student_status_id') {
            return __('Student Status');
        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        //$tabElements = $this->controller->getAcademicTabElements($options);
        $tabElements = $this->getAcademicTabElements($options);
        if($this->controller->getName() == 'GuardianNavs' || $this->controller->getName() == 'Directories') {
			$tabElements = $this->controller->getAcademicTabElements($options);
		}
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

    // public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    // {
    //     if($entity->isNew() || $entity->dirty('student_status_id')) {
    //         $id = $entity->institution_association_id;
    //         $countMale = $this->getMaleCountByAssociations($id);
    //         $countFemale = $this->getFemaleCountByAssociations($id);
    //         $this->InstitutionAssociations->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    //     }
    // }

    // public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    // {   
    //     $id = $entity->institution_association_id;
    //     $countMale = $this->getMaleCountByAssociations($id);
    //     $countFemale = $this->getFemaleCountByAssociations($id);
    //     $this->InstitutionAssociations->updateAll(['total_male_students' => $countMale, 'total_female_students' => $countFemale], ['id' => $id]);
    // }

    //POCOR-8414 start
    public function afterAction(EventInterface $event, ArrayObject $options)
    {
        $plugin = __($this->controller->getPlugin());
        if($plugin != 'Profile' && $plugin != 'GuardianNav'){
            $id = $this->request->getAttribute('params')['pass'][1];
            //POCOR-8489 --Start
            if(isset($id)) {
				$DecodedQueryString = $this->paramsDecode($id);
				$userId = $DecodedQueryString['user_id'] ?? $DecodedQueryString['student_id'];
			}else {
				$queryString = $this->getQueryString();
				$userId = $queryString['student_id'];
			}
            //POCOR-8489 --End
            $Users = TableRegistry::getTableLocator()->get('User.Users');
            $result = $Users
                ->find()
                ->select(['first_name','last_name'])
                ->where(['id' =>  $userId])
                ->first();

            $fullName = $result->first_name.' '.$result->last_name;
            try {
                
                $gettabName = 'Houses';
                $this->controller->set('contentHeader', $fullName . ' - ' . $gettabName);
                //$this->controller->set('contentHeader', $plugin);
            } catch (RecordNotFoundException $e) {
                Log::write('error', $e->getMessage());
            }
        }
    }
}
