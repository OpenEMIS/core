<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class StudentClassesTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);

        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);

        $this->addBehavior('Restful.RestfulAccessControl');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Classes' =>['id', 'institution_id', 'institution_class_id']
            ]
        ]);
        // $this->addBehavior('Student.StudentTab', [
        //     'appliedAction' => ['Classes' =>['id', 'institution_id']
        //     ]
        // ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        //$contentHeader = $this->controller->viewVars['contentHeader'];
        $contentHeader = $this->controller->viewBuilder()->getVars()['contentHeader'];
        list($studentName, $module) = explode(' - ', $contentHeader);
        $module = __('Classes');
        $contentHeader = $studentName . ' - ' . $module;
        $this->controller->set('contentHeader', $contentHeader);
        $this->controller->Navigation->substituteCrumb(__('Student Classes'), __('Classes'));
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->fields['education_grade_id']['visible'] = false;
        $this->fields['institution_id']['visible'] = false;
        $this->fields['academic_period_id']['visible'] = false;

        $this->field('academic_period', []);
        $this->field('institution', []);
        $this->field('current_class', []);
        $this->field('education_grade', []);
        $this->field('homeroom_teacher_name', []);

        $order = 0;
        $this->setFieldOrder('academic_period', $order++);
        $this->setFieldOrder('institution', $order++);
        $this->setFieldOrder('current_class', $order++);
        $this->setFieldOrder('education_grade', $order++);
        $this->setFieldOrder('institution_class_id', $order++);
        $this->setFieldOrder('homeroom_teacher_name', $order++);

        if (!empty($this->request->getQuery('institution_class_id'))) {
            $action = 'view';
            $hasAllClassesPermission = $this->AccessControl->check(['Institutions', 'AllClasses', $action]);
            $hasMyClassesPermission = $this->AccessControl->check(['Institutions', 'Classes', $action]);

            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                'view',
                $this->paramsEncode(['id' => $this->request->getQuery('institution_class_id'),'institution_id' => $this->request->getQuery('institution_id')]),
                'institution_id' => $this->request->getQuery('institution_id'),
            ];

            if ($hasAllClassesPermission) {
                return $this->controller->redirect($url);
            }

            if ($hasMyClassesPermission) {
                $userId = $this->Auth->user('id');
                if ($userId == $this->request->getQuery('staff_id') || $userId == $this->request->getQuery('secondary_staff_id')) {
                    return $this->controller->redirect($url);
                }
            }

            $this->Alert->error('security.noAccess');
        }

		// Start POCOR-5188
		if($this->request->getParam('controller') == 'Students'){
			$is_manual_exist = $this->getManualUrl('Institutions','Classes','Students - Academic');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}
		}elseif($this->request->getParam('controller') == 'Directories'){
			$is_manual_exist = $this->getManualUrl('Directory','Classes','Students - Academic');
			if(!empty($is_manual_exist)){
				$btnAttr = [
					'class' => 'btn btn-xs btn-default icon-big',
					'data-toggle' => 'tooltip',
					'data-placement' => 'bottom',
					'escape' => false,
					'target'=>'_blank'
				];

				$helpBtn['url'] = $is_manual_exist['url'];
				$helpBtn['type'] = 'button';
				$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
				$helpBtn['attr'] = $btnAttr;
				$helpBtn['attr']['title'] = __('Help');
				$extra['toolbarButtons']['help'] = $helpBtn;
			}

		}
		// End POCOR-5188

    }

    //POCOR-8490
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $userData = $this->Session->read();
        $session = $this->request->getSession(); //POCOR-6267

        if ($userData['Auth']['User']['is_guardian'] == 1) {
            /*POCOR-6267 starts*/
            if ($this->request->getParam('controller') == 'GuardianNavs') {
                $studentId = $this->getStudentID();
            }/*POCOR-6267 ends*/ else {
                $sId = $userData['Student']['ExaminationResults']['student_id'];
                $studentId = $sId ? $this->ControllerAction->paramsDecode($sId)['id'] : $this->getUserID();
            }
        } else {
            $studentId = $userData['Auth']['User']['id'];
        }

        $conditions = [];
        /*POCOR-6267 starts*/
        if ($this->request->getParam('controller') == 'GuardianNavs' || 
            (empty($userData['System']['User']['roles']) && !empty($studentId))) {
            // Set the condition for student_id if applicable
            $conditions[$this->aliasField('student_id IS')] = $studentId; //POCOR-8640
        }
        /*POCOR-6267 ends*/

        $query->contain([
            'InstitutionClasses',
            'StudentStatuses'
        ])
        ->where($conditions)
        ->toArray();
    }

    public function indexBeforeQueryOld(EventInterface $event, Query $query, ArrayObject $extra)
    {
		$userData = $this->Session->read();
        $session = $this->request->getSession();//POCOR-6267
        if ($userData['Auth']['User']['is_guardian'] == 1) {
            /*POCOR-6267 starts*/
            if ($this->request->getParam('controller') == 'GuardianNavs') {
                $studentId = $this->getStudentID();
            }/*POCOR-6267 ends*/ else {
                $sId = $userData['Student']['ExaminationResults']['student_id'];
                if ($sId) {
                    $studentId = $this->ControllerAction->paramsDecode($sId)['id'];
                } else {
                    $studentId = $this->getUserID();
                }
            }
        } else {
            $studentId = $userData['Auth']['User']['id'];
        }
		$conditions = [];
        /*POCOR-6267 starts*/
        if ($this->request->getParam('controller') == 'GuardianNavs') {
            $conditions[$this->aliasField('student_id')] = $studentId;
        }/*POCOR-6267 ends*/ else {
            if(!empty($userData['System']['User']['roles']) & !empty($userData['Student']['Students']['id'])) {
                $conditions = [];
            } else {
                if (!empty($studentId)) {
                    $conditions[$this->aliasField('student_id')] = $studentId;
                }
            }
        }

        $query->contain([
            'InstitutionClasses',
            'StudentStatuses'
        ])
		->where($conditions)
        ->toArray();
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $queryString = $this->getQueryString();
        $institutionId = $queryString['institution_id'];
        $encodedQueryString = $this->paramsEncode($queryString);
        if (isset($buttons['view'])) {
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Classes',
                0 => 'view',
                1 =>$encodedQueryString,
                2 =>$this->paramsEncode(['id' => $entity->id]),
                3 =>$this->paramsEncode(['id' => $entity->institution_class]),

            ];

            if ($this->controller->getName() == 'Directories') {
                $url = [
                    'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'StudentClasses',
                    'index',
                    'type' => 'student',
                    'staff_id' => $entity->institution_class->staff_id,
                    'secondary_staff_id' => $entity->institution_class->secondary_staff_id,
                    'institution_class_id' => $entity->institution_class->id,
                    'institution_id' => $queryString['institution_id'],
                    $encodedQueryString,
                ];
            }
            $buttons['view']['url'] = $url;
        }
        return $buttons;
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'student'];
        //$tabElements = $this->controller->getAcademicTabElements($options);
        $tabElements = $this->getAcademicTabElements($options);
        if($this->controller->getName() == 'GuardianNavs' || $this->controller->getName() == 'Directories') {
            $tabElements = $this->controller->getAcademicTabElements($options);
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Classes');
    }

    public function getClassStudents($classId, $periodId, $institutionId)
    {
        $enrolledStatus = TableRegistry::getTableLocator()->get('Student.StudentStatuses')->getIdByCode('CURRENT');
        return $this->find()
                ->contain('Users')
                ->where([
                    $this->aliasField('institution_class_id') => $classId,
                    $this->aliasField('academic_period_id') => $periodId,
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('student_status_id') => $enrolledStatus
                ])
                ->toArray();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period') {
            return __('Academic Period');
        } elseif ($field == 'institution') {
            return __('Institution');
        } elseif ($field == 'current_class') {
            return __('Current Class');
        } elseif ($field == 'education_grade') {
            return __('Education Grade');
        } elseif ($field == 'homeroom_teacher_name') {
            return __('Homeroom Teacher Name');
        } elseif ($field == 'next_institution_class_id') {
            return __('Next Institution Class');
        } elseif ($field == 'student_status_id') {
            return __('Student Status');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
