<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;

use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Workflow\Model\Behavior\WorkflowBehavior;

use Cake\Datasource\ConnectionManager; // POCOR-7578

class StaffTrainingApplicationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('staff_training_applications');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Workflow.Workflow', ['model' => 'Training.TrainingApplications']);

        $this->addBehavior('Excel',[
            'excludes' => ['staff_id','institution_id','assignee_id','training_session_id'],
            'pages' => ['index'],
        ]);
        $this->toggle('edit', false);
         $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StaffTrainingApplications' =>['id']
            ]
        ]);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        /*
        $modelAlias = 'Applications';
        $userType = 'StaffUser';
        $this->controller->changeUserHeader($this, $modelAlias, $userType);
        $this->setupTabElements();

        $session = $this->request->session();
        $extra['staffId'] = $session->read('Staff.Staff.id');
        $extra['institutionId'] = $session->read('Institution.Institutions.id');
        */

        $session = $this->request->getSession();
        if (isset($this->request->url) && $this->request->url == 'Profiles/Profiles/StaffTrainingApplications/index') {
            $session->write('Staff.Staff.id', $this->Auth->user('id'));
            $session->write('Institution.Institutions.id', $this->getInstitutionIdOfLoggedStaff());
        } else {
            $modelAlias = 'Applications';
            $userType = 'StaffUser';
            $this->controller->changeUserHeader($this, $modelAlias, $userType);
        }
        $this->setupTabElements();
        $extra['staffId'] = $session->read('Staff.Staff.id');
        $extra['institutionId'] = $session->read('Institution.Institutions.id');

                                
        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Applications','Staff - Training');       
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
		// End POCOR-5188
    }

    private function getInstitutionIdOfLoggedStaff()
    {
        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
        return $InstitutionStaff->find()->where(['InstitutionStaff.staff_id' => $this->Auth->user('id')])->first()->institution_id;
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $query = $this->ControllerAction->getQueryString();

        if (isset($extra['redirect']['query'])) {
            unset($extra['redirect']['query']);
        }

        if ($query) {
            $sessionId = $query['training_session_id'];

            // check if user has already added this session before
            $existingApplication = $this->find()
                ->where([
                    $this->aliasField('staff_id') => $extra['staffId'],
                    $this->aliasField('training_session_id') => $sessionId
                ])
                ->first();

            if (empty($existingApplication)) {
                // save session
                if ($this->saveSession($sessionId, $extra)) {
                    $this->Alert->success($this->aliasField('success'), ['reset' => true]);
                    $event->stopPropagation();
                    return $this->controller->redirect($extra['redirect']);

                } else {
                    $this->Alert->error($this->aliasField('fail'), ['reset' => true]);
                }
            } else {
                $this->Alert->warning($this->aliasField('exists'), ['reset' => true]);
            }
        }

        $event->stopPropagation();
        return $this->controller->redirect($extra['redirect']);
    }

    private function saveSession($sessionId, ArrayObject $extra)
    {
        $connection = ConnectionManager::get('default'); // POCOR-7578
        $connection->query("SET FOREIGN_KEY_CHECKS=0");  // POCOR-7578

        $staffId = $extra['staffId'];
        $institutionId = $extra['institutionId'];

        $application = [];
        $application['staff_id'] = $staffId;
        $application['training_session_id'] = $sessionId;
        $application['status_id'] = WorkflowBehavior::STATUS_OPEN;
        $application['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;
        $application['institution_id'] = $institutionId;
        $entity = $this->newEntity($application);

        if ($this->save($entity)) {
            $connection->query("SET FOREIGN_KEY_CHECKS=1");  // POCOR-7578
            return true;
        }
        $connection->query("SET FOREIGN_KEY_CHECKS=1");  // POCOR-7578
        return false;
    }

    public function indexbeforeAction(EventInterface $event, ArrayObject $extra)
    {
        if (isset($extra['redirect']['query'])) {
            unset($extra['redirect']['query']);
        }

        // add button to course catalogue
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['action'] = 'CourseCatalogue';
            $extra['toolbarButtons']['add']['url'][0] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = 'Apply';
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $user_id = ( empty($extra['staffId']) || $extra['staffId'] == '' ) ? $this->Auth->user('id') : $extra['staffId'];
        $query
            ->contain(['Sessions.Courses.TrainingFieldStudies', 'Sessions.Courses.TrainingLevels'])
            ->where([$this->aliasField('staff_id') => $user_id]);

        $extra['auto_contain_fields'] = ['Sessions.Courses' => ['credit_hours']];

        // for searching course name
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $extra['OR'] = [$this->Sessions->Courses->aliasField('name').' LIKE' => '%' . $search . '%'];
        }
    }

    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->field('course');
        $this->field('training_level');
        $this->field('field_of_study');
        $this->field('credit_hours');
        $this->field('training_session_id');
        $this->field('assignee_id', ['type' => 'hidden']);
        $this->field('staff_id', ['type' => 'hidden']);

        $this->setFieldOrder([
            'course', 'training_level', 'field_of_study', 'credit_hours', 'training_session_id'
        ]);
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Sessions.Courses.TrainingFieldStudies', 'Sessions.Courses.TrainingCourseTypes', 'Sessions.Courses.TrainingModeDeliveries', 'Sessions.Courses.TrainingRequirements', 'Sessions.Courses.TrainingLevels', 'Sessions.Courses.TargetPopulations', 'Sessions.Courses.TrainingProviders', 'Sessions.Courses.CoursePrerequisites', 'Sessions.Courses.Specialisations', 'Sessions.Courses.ResultTypes']);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields = [];
        $this->field('code');
        $this->field('course');
        $this->field('applied_session', ['type' => 'sessions']);
        $this->field('description');
        $this->field('objective');
        $this->field('credit_hours');
        $this->field('duration');
        $this->field('experiences');
        $this->field('field_of_study');
        $this->field('course_type');
        $this->field('mode_of_delivery');
        $this->field('training_requirement');
        $this->field('training_level');
        $this->field('target_populations');
        $this->field('training_providers');
        $this->field('course_prerequisites');
        $this->field('specialisations');
        $this->field('result_types');
        $this->field('attachment');

        $this->setFieldOrder([
            'code', 'course', 'applied_session', 'description', 'objective', 'credit_hours', 'duration', 'experiences',
            'field_of_study', 'course_type', 'mode_of_delivery', 'training_requirement', 'training_level',
            'target_populations', 'training_providers', 'course_prerequisites', 'specialisations', 'result_types', 'attachment'
        ]);
    }

    public function onGetCode(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->session->has('course')) {
            $value = $entity->session->course->code;
        }

        return $value;
    }

    public function onGetCourse(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->name;
        }

        return $value;
    }

    public function onGetSessionsElement(EventInterface $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'view') {
            $trainingSession = $entity->session;
            $tableHeaders = [__('Code'), __('Name'), __('Start Date'), __('End Date')];

            $tableCells = [];
            $tableCells[] = $trainingSession->code;
            $tableCells[] = $trainingSession->name;
            $tableCells[] = $this->formatDate($trainingSession->start_date);
            $tableCells[] = $this->formatDate($trainingSession->end_date);

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            return $event->getSubject()->renderElement('Institution.course_sessions', ['attr' => $attr]);
        }
    }

    public function onGetDescription(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->description;
        }

        return $value;
    }

    public function onGetObjective(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->objective;
        }

        return $value;
    }

    public function onGetCreditHours(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->credit_hours;
        }

        return $value;
    }

    public function onGetDuration(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->duration;
        }

        return $value;
    }

    public function onGetExperiences(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->number_of_months;
        }

        return $value;
    }

    public function onGetFieldOfStudy(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_field_study->name;
        }

        return $value;
    }

    public function onGetCourseType(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_course_type->name;
        }

        return $value;
    }

    public function onGetModeOfDelivery(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_mode_delivery->name;
        }

        return $value;
    }

    public function onGetTrainingRequirement(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_requirement->name;
        }

        return $value;
    }

    public function onGetTrainingLevel(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_level->name;
        }

        return $value;
    }

    public function onGetTargetPopulations(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->target_populations)) {
                $targetPopulations = $entity->session->course->target_populations;
                foreach ($targetPopulations as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetTrainingProviders(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->training_providers)) {
                $providers = $entity->session->course->training_providers;
                foreach ($providers as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetCoursePrerequisites(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->course_prerequisites)) {
                $prerequisites = $entity->session->course->course_prerequisites;
                foreach ($prerequisites as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetSpecialisations(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->specialisations)) {
                $specialisations = $entity->session->course->specialisations;
                foreach ($specialisations as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetResultTypes(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->result_types)) {
                $types = $entity->session->course->result_types;
                foreach ($types as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetAttachment(EventInterface $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course['file_name'])) {
                $courseId = $entity->session->course->id;
                $link = $event->getSubject()->HtmlField->link($entity->session->course['file_name'], [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'CourseCatalogue',
                    'download',
                    $this->paramsEncode(['id' => $courseId])
                ]);
                $value = $link;
            }
        }
        return $value;
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getTrainingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
    }
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        foreach ($cloneFields as $key => $value) {
            $newFields[] = $value;
            if($value['field'] == 'status_id'){
                $newFields[] = [
                    'key' => 'TrainingCourses.name',
                    'field' => 'course_name',
                    'type' => 'string',
                    'label' => 'Course'
                ];
    
                $newFields[] = [
                    'key' => 'TrainingLevels.name',
                    'field' => 'training_level_name',
                    'type' => 'string',
                    'label' => 'Training Level'
                ];
    
                $newFields[] = [
                    'key' => 'TrainingFieldOfStudies.name',
                    'field' => 'training_study_of_fields',
                    'type' => 'string',
                    'label' => 'Field Of Study'
                ];
    
                $newFields[] = [
                    'key' => 'TrainingCourses.credit_hours',
                    'field' => 'credit_hours',
                    'type' => 'string',
                    'label' => 'Credit Hours'
                ];

                $newFields[] = [
                    'key'   => 'StaffTrainingApplications.training_session_id',
                    'field' => 'training_session_id',
                    'type'  => 'string',
                    'label' => __('Training Session')
                ];
            }
        }
        $fields->exchangeArray($newFields);
    }
    
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->getSession();
        $institutionId = $this->getInstitutionID();
        $trainingSession = TableRegistry::getTableLocator()->get('Training.TrainingSessions');
        $trainingCourses = TableRegistry::getTableLocator()->get('Training.TrainingCourses');
        $trainingLevels = TableRegistry::getTableLocator()->get('Training.TrainingLevels');
        $trainingFieldOfStudies = TableRegistry::getTableLocator()->get('Training.TrainingFieldStudies');
        $workflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        $staffId = $this->getStaffID();
        $status = $this->request->getQuery('category');
    
        $query
        ->select([
            'course_name' => 'TrainingCourses.name',
            'training_level_name' => 'TrainingLevels.name',
            'training_study_of_fields' => 'TrainingFieldStudies.name',
            'credit_hours' => 'TrainingCourses.credit_hours'
        ])
        ->leftJoin([$trainingSession->getAlias() => $trainingSession->getTable()],[
            $trainingSession->aliasField('id = ').$this->aliasField('training_session_id')
        ])
        ->leftJoin([$trainingCourses->getAlias() => $trainingCourses->getTable()],[
            $trainingCourses->aliasField('id = ').$trainingSession->aliasField('training_course_id')
        ])
        ->leftJoin([$trainingLevels->getAlias() => $trainingLevels->getTable()],[
            $trainingLevels->aliasField('id = ').$trainingCourses->aliasField('training_level_id')
        ])
        ->leftJoin([$trainingFieldOfStudies->getAlias() => $trainingFieldOfStudies->getTable()],[
            $trainingFieldOfStudies->aliasField('id = ').$trainingCourses->aliasField('training_field_of_study_id')
        ])
        ->innerJoin([$workflowSteps->getAlias() => $workflowSteps->getTable()],[
            $workflowSteps->aliasField('id = ').$this->aliasField('status_id')
        ])
        ->where([
            'institution_id =' .$institutionId,
            $this->aliasField('staff_id') => $staffId
        ]);

        if($status > 0){
            $query
            ->where([
                $workflowSteps->aliasField('category = ') => $status
            ]); 
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'course') {
            return __('Course');
        } elseif ($field == 'training_level') {
            return __('Training Level');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'applied_session') {
            return __('Applied Session');
        } elseif ($field == 'description') {
            return __('Description');
        } elseif ($field == 'objective') {
            return __('Objective');
        } elseif ($field == 'duration') {
            return __('Duration');
        } elseif ($field == 'duration') {
            return __('Duration');
        } elseif ($field == 'credit_hours') {
            return __('Credit Hours');
        } elseif ($field == 'experiences') {
            return __('Experiences');
        } elseif ($field == 'field_of_study') {
            return __('Field Of Study');
        } elseif ($field == 'course_type') {
            return __('Course Type');
        } elseif ($field == 'mode_of_delivery') {
            return __('Mode Of Delivery');
        } elseif ($field == 'training_requirement') {
            return __('Training Requirement');
        } elseif ($field == 'training_requirement') {
            return __('Training Requirement');
        } elseif ($field == 'target_populations') {
            return __('Target Populations');
        } elseif ($field == 'training_requirement_id') {
            return __('Training Requirement');
        } elseif ($field == 'training_priority_id') {
            return __('Training Priority');
        } elseif ($field == 'training_providers') {
            return __('Training Providers');
        } elseif ($field == 'course_prerequisites') {
            return __('Course Prerequisites');
        } elseif ($field == 'specialisations') {
            return __('Specialisations');
        } elseif ($field == 'result_types') {
            return __('Result Types');
        } elseif ($field == 'attachment') {
            return __('Attachment');
        } elseif ($field == 'reason') {
            return __('Reason');
        } elseif ($field == 'status_id') {
            return __('Status');
        } elseif ($field == 'status_id') {
            return __('Status');
        } elseif ($field == 'training_need_category_id') {
            return __('Training Need Category');
        } elseif ($field == 'assignee_id') {
            return __('Assignee');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'institution_id') {
            return __('Institution');
        }elseif ($field == 'training_session_id') {
            return __('Training Session');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
