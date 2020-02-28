<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Institution\Model\Table\InstitutionStaffReleasesTable;

// This table is StaffReleaseOut table - Table name to match with workflow model alias in order to Display as Staff Release in email
class StaffReleaseTable extends InstitutionStaffReleasesTable
{
    CONST INSTITUTION_ACTIVE = 1;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');

        if ($this->behaviors()->has('Workflow')) {
            $this->behaviors()->get('Workflow')->config([
                'institution_key' => 'previous_institution_id'
            ]);
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator->notEmpty(['positions_held','previous_end_date', 'workflow_assignee_id'])
            ->add('previous_end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'position_start_date', false]
            ]);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            if ($data->offsetExists('positions_held')) {
                $institutionStaffId = $data->offsetGet('positions_held');
                $data->offsetSet('previous_institution_staff_id', $institutionStaffId);
            }

            if ($data->offsetExists('position_start_date')) {
                $startDate = new Date($data->offsetGet('position_start_date'));
                $data->offsetSet('previous_start_date', $startDate->format('Y-m-d'));
            }
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        parent::beforeAction($event, $extra);
        $this->field('previous_institution_staff_id', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }

        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('previous_start_date', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);
        $this->field('new_institution_position_id', ['type' => 'hidden']);
        $this->field('new_staff_type_id', ['type' => 'hidden']);
        $this->field('new_FTE', ['type' => 'hidden']);
        $this->field('new_start_date', ['type' => 'hidden']);
        $this->field('new_end_date', ['type' => 'hidden']);

        $this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        $this->field('new_institution_id', ['type' => 'integer', 'sort' => ['field' => 'NewInstitutions.code']]);
        $this->field('previous_end_date', ['sort' => ['field' => 'previous_end_date']]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'staff_id', 'new_institution_id', 'previous_end_date']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

        $query->find('InstitutionStaffRelease', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code'], 'NewInstitutions' => ['code']];

        // sort
        $sortList = ['assignee_id', 'NewInstitutions.code', 'previous_end_date'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Users', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');
        $userId = $this->getQueryString('user_id');

        if (empty($userId)) {
            $event->stopPropagation();
            return $this->controller->redirect($this->url('index'));
        } else {
            // url to redirect to staffUser page
            $staffUserUrl = $this->url('view');
            $staffUserUrl['action'] = 'StaffUser';
            $staffUserUrl[1] = $this->paramsEncode(['id' => $userId]);

            //check pending release to be done
            $pendingRelease = $this->find()
                ->matching('Statuses.WorkflowStepsParams', function ($q) {
                    return $q->where(['WorkflowStepsParams.name' => 'institution_owner']);
                })
                ->where([
                    $this->aliasField('staff_id') => $userId,
                    $this->Statuses->aliasField('category <> ') => self::DONE
                ])
                ->first();

            if (!empty($pendingRelease)) {
                // check if the outgoing institution can view the release record
                $visible = 0;
                if ($pendingRelease->previous_institution_id == $institutionId) {
                    $institutionOwner = $pendingRelease->_matchingData['WorkflowStepsParams']->value;
                    if ($institutionOwner == self::OUTGOING || $pendingRelease->all_visible) {
                        $visible = 1;
                    }
                }

                if ($visible) {
                    $url = $this->url('view');
                    $url[1] = $this->paramsEncode(['id' => $pendingRelease->id]);
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                } else {
                    $this->Alert->warning($this->aliasField('existingStaffTransfer'), ['reset' => true]);
                    $event->stopPropagation();
                    return $this->controller->redirect($staffUserUrl);
                }
            }

            // if no pending release
            $StaffTable = TableRegistry::get('Institution.Staff');
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');

            $institutionStaffEntity = $StaffTable->find()
                ->contain(['Users', 'Institutions'])
                ->where([
                    $StaffTable->aliasField('staff_id') => $userId,
                    $StaffTable->aliasField('institution_id') => $institutionId,
                    $StaffTable->aliasField('staff_status_id') => $assignedStatus,
                ])
                ->first();
            $this->setupFields($institutionStaffEntity);

            $extra['toolbarButtons']['back']['url'] = $staffUserUrl;
        }
    }

    private function setupFields(Entity $entity)
    {
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Release From')]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('previous_institution_id', ['entity' => $entity]);
        $this->field('positions_held', ['entity' => $entity]);

        // to populate current institution staff fields based on selected positions_held
        $FTE = $staffType = $startDate = $startDateFormatted = '';
        if (isset($this->request->data[$this->alias()]['positions_held']) && !empty($this->request->data[$this->alias()]['positions_held'])) {
            $institutionStaffId = $this->request->data[$this->alias()]['positions_held'];
            $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId, ['contain' => ['StaffTypes']]);
            if (!empty($staffEntity)) {
                $FTE = $this->fteOptions["$staffEntity->FTE"];
                $valueOfFTE =  $staffEntity->FTE;
                $staffTypeId = $staffEntity->staff_type_id;
                $staffType = $staffEntity->staff_type->name;
                $startDate = $staffEntity->start_date->format('Y-m-d');
                $startDateFormatted = $this->formatDate($staffEntity->start_date);
            }
        }

        $this->field('FTE', ['type' => 'readonly', 'attr' => ['value' => $FTE]]);
        $this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $staffType]]);
        $this->field('position_start_date', ['type' => 'readonly', 'value' => $startDate, 'attr' => ['value' => $startDateFormatted]]);
        $this->field('previous_start_date', ['type' => 'hidden']);
        $this->field('previous_end_date', ['entity' => $entity]);
        $this->field('previous_FTE', ['type' => 'hidden', 'attr' => ['value' => $valueOfFTE]]);
        $this->field('previous_staff_type_id', ['type' => 'hidden', 'attr' => ['value' => $staffTypeId]]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Release To')]);
        $this->field('new_institution_id', ['entity' => $entity]);
        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
        $this->field('comment');

        $this->field('new_institution_position_id', ['type' => 'hidden']);
        $this->field('new_staff_type_id', ['type' => 'hidden']);
        $this->field('new_FTE', ['type' => 'hidden']);
        $this->field('new_start_date', ['type' => 'hidden']);
        $this->field('new_end_date', ['type' => 'hidden']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Release From')]);
        $this->field('new_information_header', ['type' => 'section', 'title' => __('Release To')]);
        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
        $this->field('positions_held', ['entity' => $entity]);
        $this->field('FTE');
        $this->field('staff_type_id');
        $this->field('position_start_date');

        $this->field('institution_position_id', ['type' => 'hidden']);
        $this->field('previous_start_date', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);
        $this->field('new_institution_position_id', ['type' => 'hidden']);
        $this->field('new_staff_type_id', ['type' => 'hidden']);
        $this->field('new_FTE', ['type' => 'hidden']);
        $this->field('new_start_date', ['type' => 'hidden']);
        $this->field('new_end_date', ['type' => 'hidden']);
        
        if($entity->new_institution_id == $entity->previous_institution_id){
            $this->setFieldOrder([
                'previous_information_header', 'staff_id', 'previous_institution_id', 'institution_position_id', 'FTE', 'staff_type_id', 'position_start_date', 'previous_end_date',
                'previous_start_date', 'previous_FTE', 'previous_staff_type_id',
                'new_information_header', 'new_start_date',
                'transfer_reasons_header', 'comment',
                // hidden fields
                'all_visible', 'previous_institution_staff_id','previous_staff_type_id'
            ]);
        }else{
            $this->setFieldOrder([
                'previous_information_header', 'staff_id', 'previous_institution_id', 'institution_position_id', 'FTE', 'staff_type_id', 'position_start_date', 'previous_end_date',
                'previous_start_date', 'previous_FTE', 'previous_staff_type_id',
                'new_information_header', 'new_institution_id', 'new_start_date',
                'transfer_reasons_header', 'comment',
                // hidden fields
                'all_visible', 'previous_institution_staff_id','previous_staff_type_id'
            ]);
        }
        
    }

    public function onGetInstitutionPositionId(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffEntity = $this->PreviousInstitutionStaff->get($entity->previous_institution_staff_id, ['contain' => ['Positions']]);
            $value = $StaffEntity->position->name;
        }
        return $value;
    }

    public function onGetFTE(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffEntity = $this->PreviousInstitutionStaff->get($entity->previous_institution_staff_id);
            $value = $this->fteOptions["$StaffEntity->FTE"];
        }
        return $value;
    }

    public function onGetPositionStartDate(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffEntity = $this->PreviousInstitutionStaff->get($entity->previous_institution_staff_id);
            $value = $this->formatDate($StaffEntity->start_date);
        }
        return $value;
    }

    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->staff_id;
            $attr['attr']['value'] = $entity->user->name_with_id;
            return $attr;
        }
    }

    public function onUpdateFieldPreviousInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];
            if ($action == 'add') {
                // using institution_staff entity
                $attr['value'] = $entity->institution_id;
                $attr['attr']['value'] = $entity->institution->code_name;
            } else {
                // using institution_staff_transfer entity
                $attr['value'] = $entity->previous_institution_id;
                $attr['attr']['value'] = $entity->previous_institution->code_name;
            }
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldPreviousEndDate(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $attr['null'] = false;
            return $attr;
        }
    }

    public function onUpdateFieldPositionsHeld(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $entity = $attr['entity'];

            if ($this->action == 'add') {
                // using institution_staff entity
                $institutionId = $entity->institution_id;
            } else {
                // using institution_staff_transfer entity
                $institutionId = $entity->previous_institution_id ;
            }

            $staffEntity = $this->PreviousInstitutionStaff->find()
                ->select([
                    $this->PreviousInstitutionStaff->aliasField('id'),
                    'Positions.position_no',
                    'Positions.staff_position_title_id'
                ])
                ->matching('Positions')
                ->where([
                    $this->PreviousInstitutionStaff->aliasField('institution_id') => $institutionId,
                    $this->PreviousInstitutionStaff->aliasField('staff_id') => $entity->staff_id,
                    $this->PreviousInstitutionStaff->aliasField('staff_status_id') => $StaffStatuses->getIdByCode('ASSIGNED')
                ])
                ->order([$this->PreviousInstitutionStaff->aliasField('created') => 'DESC'])
                ->toArray();

            $options = [];
            foreach ($staffEntity as $staff) {
                $options[$staff->id] = $staff->_matchingData['Positions']->name;
            }

            if (!isset($this->request->data[$this->alias()]['positions_held'])) {
                reset($options);
                $this->request->data[$this->alias()]['positions_held'] = key($options);
            }

            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = $options;
            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldNewInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit', 'approve'])) {
            $entity = $attr['entity'];

            if ($action == 'add') {
                // using institution_staff entity
                $conditions = [];
                $conditions[$this->NewInstitutions->aliasField('id <>')] = $entity->institution_id;
                $conditions[$this->NewInstitutions->aliasField('institution_status_id')] = self::INSTITUTION_ACTIVE;

                $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
                $Institutions = TableRegistry::get('Institution.Institutions');

                //restrict staff release between same type
                $restrictStaffTransferByType = $ConfigItems->value('restrict_staff_release_between_same_type');
               
                $conditionsInstitutionTypeId = [];
                if ($restrictStaffTransferByType) {
                    if ($entity->has('institution_id')) {
                        $institutionId = $entity->institution_id;
                        $conditionsInstitutionTypeId['institution_type_id'] = $Institutions->get($institutionId)->institution_type_id;
                    }
                }
                
                //Restrict Staff Release Between Different Provider
                $restrictStaffReleaseBetweenDifferentProvider = $ConfigItems->value('restrict_staff_release_between_different_provider');

                if ($restrictStaffReleaseBetweenDifferentProvider && 
                    $entity->has('institution_id')    
                    ) {                    
                        $institutionId = $entity->institution_id;
                        $conditions['institution_provider_id'] = $Institutions->get($institutionId)->institution_provider_id;                        
                }
               
                $options = $this->NewInstitutions->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ]);
                
                if(!empty($conditionsInstitutionTypeId)){
                    $options->where([$conditions, ['OR'=>$conditionsInstitutionTypeId]]);
                }else{
                    $options->where($conditions);
                }
                  
                $options->order($this->NewInstitutions->aliasField('code'))
                        ->toArray();
                
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = true;
                $attr['options'] = $options;
            } else {
                // using institution_staff_transfer entity
                $attr['type'] = 'readonly';
                $attr['value'] = $entity->new_institution_id;
                $attr['attr']['value'] = $entity->new_institution->code_name;
            }
            return $attr;
        }
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        if (!empty($entity->previous_institution_staff_id)) {
            $this->request->data[$this->alias()]['positions_held'] = $entity->previous_institution_staff_id;
        }
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'NewInstitutions', 'PreviousInstitutions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }
    
    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        if(empty($entity->new_institution_id)){
            $StaffTable = TableRegistry::get('Institution.Staff');
            $StaffStatusesTable = TableRegistry::get('Staff.StaffStatuses');
            $staffStatusId = $StaffStatusesTable->getIdByCode('END_OF_ASSIGNMENT');
            $StaffTable->updateAll(['staff_status_id' => $staffStatusId], ['staff_id'=>$entity->staff_id]);            
            $entity->new_institution_id = $entity->previous_institution_id;
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        // redirect to view page of record after save
        $extra['redirect'][0] = 'view';
        $extra['redirect'][1] = $this->paramsEncode(['id' => $entity->id]);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $StepsParams = $this->Statuses->WorkflowStepsParams;
        $doneStatus = self::DONE;
        $outgoingInstitution = self::OUTGOING;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('new_institution_id'),
                $this->aliasField('previous_institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name'),
                $this->NewInstitutions->aliasField('code'),
                $this->NewInstitutions->aliasField('name'),
                $this->PreviousInstitutions->aliasField('code'),
                $this->PreviousInstitutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias(), $this->NewInstitutions->alias(), $this->PreviousInstitutions->alias(), $this->CreatedUser->alias()])
            ->matching($Statuses->alias().'.'.$StepsParams->alias(), function ($q) use ($Statuses, $StepsParams, $doneStatus, $outgoingInstitution) {
                return $q->where([
                    $Statuses->aliasField('category <> ') => $doneStatus,
                    $StepsParams->aliasField('name') => 'institution_owner',
                    $StepsParams->aliasField('value') => $outgoingInstitution
                ]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffRelease',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->previous_institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s to %s'), $row->user->name_with_id, $row->new_institution->code_name);
                    $row['institution'] = $row->previous_institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });
        return $query;
    }

}
