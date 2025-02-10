<?php

namespace Workflow\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;

class WorkflowsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->ControllerAction->models = [
            'Workflows' => ['className' => 'Workflow.Workflows', 'options' => ['deleteStrategy' => 'transfer']],
            'Steps' => ['className' => 'Workflow.WorkflowSteps', 'options' => ['deleteStrategy' => 'restrict']],
            'Actions' => ['className' => 'Workflow.WorkflowActions'],
            'Statuses' => ['className' => 'Workflow.WorkflowStatuses'],
            'WorkflowSteps' => ['className' => 'Workflow.WorkflowSteps', 'options' => ['deleteStrategy' => 'restrict']],
            'WorkflowStatuses' => ['className' => 'Workflow.WorkflowStatuses', 'options' => ['deleteStrategy' => 'restrict']],
            'WorkflowActions' => ['className' => 'Workflow.WorkflowActions', 'options' => ['deleteStrategy' => 'restrict']],
        ];
        $this->loadComponent('Paginator');
    }

    // CAv4
    public function Rules()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Workflow.WorkflowRules']);
    }

    // End


    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $hasWorkflowsAccess = $this->AccessControl->check([$this->getName(), 'Workflows', 'view']);
        $hasStepsAccess = $this->AccessControl->check([$this->getName(), 'Steps', 'view']);
        $hasActionsAccess = $this->AccessControl->check([$this->getName(), 'Actions', 'view']);
        $hasRulesAccess = $this->AccessControl->check([$this->getName(), 'Rules', 'view']);
        $hasStatusesAccess = $this->AccessControl->check([$this->getName(), 'Statuses', 'view']);

        $tabElements = [];
        if ($hasWorkflowsAccess) {
            $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Workflows'];
            $paramsQuery = $this->paramsQuery(['model']);
            $url = array_merge($url, $paramsQuery);

            $tabElements['Workflows'] = [
                'url' => $url,
                'text' => __('Workflows')
            ];
        }

        if ($hasStepsAccess) {
            $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Steps'];
            $paramsQuery = $this->paramsQuery(['model', 'workflow']);
            $url = array_merge($url, $paramsQuery);

            $tabElements['Steps'] = [
                'url' => $url,
                'text' => __('Steps')
            ];
        }

        if ($hasActionsAccess) {
            $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Actions'];
            $paramsQuery = $this->paramsQuery();
            $url = array_merge($url, $paramsQuery);

            $tabElements['Actions'] = [
                'url' => $url,
                'text' => __('Actions')
            ];
        }

        if ($hasRulesAccess) {
            $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Rules'];
            $paramsQuery = $this->paramsQuery();
            $url = array_merge($url, $paramsQuery);

            $tabElements['Rules'] = [
                'url' => $url,
                'text' => __('Rules')
            ];
        }

        if ($hasStatusesAccess) {
            $url = ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => 'Statuses'];
            $paramsQuery = $this->paramsQuery(['model']);
            $url = array_merge($url, $paramsQuery);

            $tabElements['Statuses'] = [
                'url' => $url,
                'text' => __('Statuses')
            ];
        }

        $selectedAction = $this->request->getParam('action');
        // add this logic to highlight the tab correctly
        if (!$hasWorkflowsAccess && !$hasStepsAccess && !$hasActionsAccess) {
            $selectedAction = 'Statuses';
        } else if (!$hasWorkflowsAccess && !$hasStepsAccess) {
            $selectedAction = 'Actions';
        } else if (!$hasWorkflowsAccess) {
            $selectedAction = 'Steps';
        }

        if (in_array('Cases', (array)Configure::read('School.excludedPlugins'))) {
            if (isset($tabElements['Rules'])) {
                unset($tabElements['Rules']);
            }
        }

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->getParam('action'));
    }

    private function paramsQuery($keys = [])
    {
        $requestQuery = $this->request->getQuery();

        if (!empty($keys)) {
            $params = [];
            foreach ($keys as $value) {
                if (array_key_exists($value, $requestQuery)) {
                    $params[$value] = $requestQuery[$value];
                }
            }

            if (!empty($params)) {
                return $params;
            }
        }

        return $requestQuery;
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra)
    {
        $header = __('Workflow');

        $header .= ' - ' . $model->getHeader($model->getAlias());
        $this->Navigation->addCrumb('Workflow', ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $model->alias]);
        $this->Navigation->addCrumb($model->getHeader($model->getAlias()));

        $this->set('contentHeader', $header);
    }

    public function ajaxGetCases()
    {
        $this->viewBuilder()->setLayout('ajax');
        /*
         - missing institution_id is profile->staff->carrer
         -Start POCOR-6619
        */

        $url = $_SERVER['HTTP_REFERER'];
        $queryString = parse_url($url);
        $urlInstitutionId = $queryString['query'];
        $getInstitutionId = explode("=", $urlInstitutionId);
        //End POCOR-6619

        $isSchoolBased = $this->request->getQuery('is_school_based');
        $nextStepId = $this->request->getQuery('next_step_id');
        $autoAssignAssignee = $this->request->getQuery('auto_assign_assignee');
        $case_id = $this->request->getQuery('case_id');


        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $params = [
            'is_school_based' => $isSchoolBased,
            'workflow_step_id' => $nextStepId,
            'url_institution_id' => $getInstitutionId[1]  //POCOR-6619
        ];
        if ($isSchoolBased) {
            $session = $this->request->getSession();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
                $params['institution_id'] = $institutionId;
            }
        }
        $institutionCasesT = TableRegistry::get('Cases.InstitutionCases');
        $caseOptions = $institutionCasesT->find('list', ['keyField' => 'id', 'valueField' => 'case_number'])->where(['id !=' => $case_id])->toArray();


        // $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params);
        // echo "<pre>"; print_r($caseOptions);die;
//        Log::write('debug', 'CaseLink:');
//        Log::write('debug', print_r($caseOptions, true));

        $defaultKey = empty($caseOptions) ? __('No options') : '-- ' . __('Select') . ' --';
        $options = $caseOptions;

        // } else {
        //     Log::write('debug', 'Auto Assign Assignee');

        //     $defaultKey = '';
        //     $options = [$this->Auth->user('id') => __('Auto Assign')]; //POCOR-7080
        // }

        $responseData = [
            'default_key' => $defaultKey,
            'cases' => $options
        ];

        $this->response = $this->response->withStringBody(json_encode($responseData, JSON_UNESCAPED_UNICODE));
        $this->response = $this->response->withType('json');

        return $this->response;
    }

    public function ajaxGetAssignees()
    {
        $this->viewBuilder()->setLayout('ajax');
        /*
         - missing institution_id is profile->staff->carrer
         -Start POCOR-6619
        */
        $url = $_SERVER['HTTP_REFERER'];
        $queryString = parse_url($url);
        $urlInstitutionId = $queryString['query'];
        $getInstitutionId = explode("=",$urlInstitutionId);

        try {
            $institutionID = $this->paramsDecode($getInstitutionId[1])['institution_id'];
        } catch (\Exception $exception) {
            $institutionID = $this->getInstitutionIDFromUrl($url);
        }

        //End POCOR-6619

        $isSchoolBased = $this->request->getQuery('is_school_based');
        $nextStepId = $this->request->getQuery('next_step_id');
        $autoAssignAssignee = $this->request->getQuery('auto_assign_assignee');

        if (!$autoAssignAssignee) {
            $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
            $params = [
                'is_school_based' => $isSchoolBased,
                'workflow_step_id' => $nextStepId,
                'url_institution_id' => $getInstitutionId[1]  //POCOR-6619
            ];

            if ($isSchoolBased) {
                //$institutionId = $this->paramsDecode($getInstitutionId[1])['institution_id'];
                if (!empty($institutionID)) {
                    $params['institution_id'] = $institutionID;
                }
                /*$session = $this->request->getSession();
                if ($session->check('Institution.Institutions.id')) {
                    $institutionId = $session->read('Institution.Institutions.id') ;
                    $params['institution_id'] = $institutionId;
                }*/
            }

            $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params);

//            Log::write('debug', 'Assignee:');
//            Log::write('debug', print_r($assigneeOptions, true));

            $defaultKey = empty($assigneeOptions) ? __('No options') : '-- ' . __('Select') . ' --';
            $options = $assigneeOptions;

        } else {
            //POCOR-8642 --START
            $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
            $path = $queryString['path'];
            $segments = explode('/', $path);
            //echo "<pre>";print_r($_SESSION);exit;
            if (count($segments) > 0) {
                $institutionIndex = array_search('Institutions', $segments);
                if ($institutionIndex !== false && isset($segments[$institutionIndex + 1])) {
                    $transferType = $segments[$institutionIndex + 1];
                } else {
                    $transferType = '';
                }
            } else {
                $transferType = '';
            }

            if ($transferType === 'StudentTransferOut' || $transferType === 'StudentTransferIn') {
                $tableName = 'Institution.StudentTransferOut';
                $primaryKey = $_SESSION['Institution'][$transferType]['primaryKey']; // Fetching primaryKey for StudentTransferOut
            } elseif ($transferType === 'StaffTransferOut' || $transferType === 'StaffTransferIn') {
                $tableName = 'Institution.StaffTransferOut';
                $primaryKey = $_SESSION['Institution'][$transferType]['primaryKey']; // Fetching primaryKey for StaffTransferOut
            }

            $id = isset($primaryKey['id']) ? $primaryKey['id'] : null;
            $institutionId = isset($primaryKey['institution_id']) ? $primaryKey['institution_id'] : null;
            $receivingInsttutionId = TableRegistry::get($tableName)->getReceivingInstList($id);

            $params = [
                'is_school_based' => $isSchoolBased,
                'workflow_step_id' => $nextStepId,
                'url_institution_id' => $getInstitutionId[1]  //POCOR-6619
            ];

            if ($isSchoolBased) {
                if (!empty($institutionID)) {
                    $params['institution_id'] = $receivingInsttutionId;
                }
            }

            $assigneeOptions = $SecurityGroupUsers->getAssigneeList($params);

//            Log::write('debug', 'Assignee:');
//            Log::write('debug', print_r($assigneeOptions, true));

            $defaultKey = empty($assigneeOptions) ? __('No options') : '-- ' . __('Select') . ' --';
            $options = $assigneeOptions;

            if(empty($options)) {
//                Log::write('debug', 'Auto Assign Assignee');

                $defaultKey = '';
                $options = [$this->Auth->user('id') => __('Auto Assign')]; //POCOR-7080
            }
            //POCOR-8642 --END
        }

        $responseData = [
            'default_key' => $defaultKey,
            'assignees' => $options
        ];

        $this->response = $this->response->withStringBody(json_encode($responseData, JSON_UNESCAPED_UNICODE));
        $this->response = $this->response->withType('json');

        return $this->response;
    }

    private function getInstitutionIDFromUrl($url)
    {
        $viewIndex = strpos($url, '/view/');
        if ($viewIndex !== false) {
            // Find the position of the next /
            $nextSlashIndex = strpos($url, '?', $viewIndex + 6); // Adding 6 to skip /view/

            if ($nextSlashIndex !== false) {
                // Extract the substring between /view/ and the next /
                $viewParamValue = substr($url, $viewIndex + 6, $nextSlashIndex - ($viewIndex + 6));
                // Now $viewParamValue contains the value of the 'view' parameter
            } else {
               $viewParamValue = substr($url, $viewIndex + 6);
            }
        } else {
            // 'view' parameter is not present in the URL
            $viewParamValue = "";
        }

        $institutionID = -1;
        if ($viewParamValue) {
            $params = $this->paramsDecode($viewParamValue);
            $institutionID = $params['institution_id'];
        }
        return $institutionID;
    }

    public function ajaxUpdateComment()
    {
        $this->viewBuilder()->setLayout('ajax');

        $url = $_SERVER['HTTP_REFERER'];
        $queryString = parse_url($url);

        $comment = $this->request->getQuery('name');
        $case_id = $this->request->getQuery('caseId');

        $workflow_transitions_table = TableRegistry::get('Workflow.WorkflowTransitions');

        $dataRecord = $workflow_transitions_table->get($case_id);
        $dataRecord->comment = $comment;
        $workflow_transitions_table->save($dataRecord);

//        Log::write('debug', 'Update case comment:');

        $responseData = [
            'default_key' => 'success'
        ];

        $this->response = $this->response->withStringBody(json_encode($responseData, JSON_UNESCAPED_UNICODE));
        $this->response = $this->response->withType('json');

        return $this->response;
    }

    public function ajaxGetComment()
    {
        //$this->viewBuilder()->layout('ajax');

        $this->viewBuilder()->setLayout('ajax');

        $url = $_SERVER['HTTP_REFERER'];
        $queryString = parse_url($url);

        $case_id = $this->request->getQuery('caseId');
        $workflow_transitions_table = TableRegistry::get('Workflow.WorkflowTransitions');
        $data = $workflow_transitions_table->find()->where(['id' => $case_id])->first();
        $comment = $data->comment;

//        Log::write('debug', 'CaseLink:');
//        Log::write('debug', print_r($comment, true));

        $responseData = [
            'default_key' => 'Success',
            'comment' => $comment
        ];

        $this->response = $this->response->withStringBody(json_encode($responseData, JSON_UNESCAPED_UNICODE));
        $this->response = $this->response->withType('json');

        return $this->response;
    }

    public function ajaxDelCase()
    {
        //$this->viewBuilder()->layout('ajax');
        $this->viewBuilder()->setLayout('ajax');
        $url = $_SERVER['HTTP_REFERER'];
        $queryString = parse_url($url);

        $case_id = $this->request->getQuery('caseId');
        $workflow_transitions_table = TableRegistry::get('Workflow.WorkflowTransitions');
        $params = [
            'caseId' => $case_id
        ];
        if ($case_id) {
            $entity = $workflow_transitions_table->get($case_id);
            $success = $workflow_transitions_table->delete($entity);
        }

//        Log::write('debug', 'Delete case comment:');


        $responseData = [
            'default_key' => 'success'
        ];

        $this->response = $this->response->withStringBody(json_encode($responseData, JSON_UNESCAPED_UNICODE));
        $this->response = $this->response->withType('json');

        return $this->response;
    }

    public
    function getInstitutionID($debugString = "")
    {
        // POCOR-8115;
        // institution_id should always be in query string, if not, die as an error
        $institution_id =  $this->getQueryString('institution_id');
        if (!$institution_id) {
            if ($debugString != "") {
                die($debugString . 'For Developer: You should put institution_id into query string first');
            }
        }
        return $institution_id;
    }
}
