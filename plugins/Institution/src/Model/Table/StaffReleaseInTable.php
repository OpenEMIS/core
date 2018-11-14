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

class StaffReleaseInTable extends InstitutionStaffReleasesTable
{
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        parent::beforeAction($event, $extra);

        $this->field('previous_institution_staff_id', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('transfer_type', ['type' => 'hidden']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('previous_institution_id', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('previous_start_date', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);
        $this->field('comment', ['type' => 'hidden']);
        $this->field('new_institution_id', ['type' => 'hidden']);
        $this->field('new_institution_position_id', ['type' => 'hidden']);
        $this->field('new_staff_type_id', ['type' => 'hidden']);
        $this->field('new_FTE', ['type' => 'hidden']);
        $this->field('new_start_date', ['type' => 'hidden']);
        $this->field('new_end_date', ['type' => 'hidden']);

        //$this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        //$this->field('current_institution');
        $this->field('assignee_id', ['sort' => ['field' => 'assignee_id']]);
        $this->field('current_institution', ['sort' => ['field' => 'PreviousInstitutions.code']]);
        $this->field('start_date', ['sort' => ['field' => 'new_start_date']]);
        $this->field('institution_position');
        // $this->field('new_start_date', ['sort' => ['field' => 'new_start_date']]);
        // $this->field('new_institution_position_id');

        $this->setFieldOrder(['status_id', 'assignee_id', 'staff_id', 'current_institution', 'new_start_date', 'new_institution_position_id']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $institutionId = isset($this->request->params['institutionId']) ? $this->paramsDecode($this->request->params['institutionId'])['id'] : $session->read('Institution.Institutions.id');

        $query->find('InstitutionStaffReleaseIn', ['institution_id' => $institutionId]);
        $extra['auto_contain_fields'] = ['PreviousInstitutions' => ['code'], 'NewInstitutions' => ['code']];

        // sort
        $sortList = ['assignee_id', 'PreviousInstitutions.code', 'new_start_date'];
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Release From')]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('current_institution', ['entity' => $entity]);
        $this->field('positions_held', ['entity' => $entity]);

        // to populate current institution staff fields based on selected positions_held
        $FTE = $staffType = $startDate = $startDateFormatted = '';
        if (isset($this->request->data[$this->alias()]['positions_held']) && !empty($this->request->data[$this->alias()]['positions_held'])) {
            $institutionStaffId = $this->request->data[$this->alias()]['positions_held'];
            $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId, ['contain' => ['StaffTypes']]);
            if (!empty($staffEntity)) {
                $FTE = $this->fteOptions["$staffEntity->previous_FTE"];
                $staffType = $staffEntity->staff_type->name;
                $startDate = $staffEntity->start_date->format('Y-m-d');
                $startDateFormatted = $this->formatDate($staffEntity->start_date);
            }
        }

        $this->field('FTE', ['type' => 'readonly', 'attr' => ['value' => $FTE]]);
        $this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $staffType]]);
        $this->field('position_start_date');
        $this->field('position_end_date');
        $this->field('previous_start_date', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Release To')]);
        $this->field('new_institution_id', ['entity' => $entity]);
        $this->field('new_staff_type_id');
        $this->field('new_FTE');
        $this->field('new_institution_position_id');
        $this->field('new_start_date');
        $this->field('new_end_date');


        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
        $this->field('comment');
    }

    public function onGetStaffId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name_with_id;
        }
        return $value;
    }

    public function onGetStaffTypeId(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffEntity = $this->PreviousInstitutionStaff->get($entity->previous_institution_staff_id, ['contain' => ['StaffTypes']]);
            $value = $StaffEntity->staff_type->name;
        }
        return $value;
    }

    public function onGetCurrentInstitution(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->previous_institution)) {
            $value = $entity->previous_institution->name;
        }
        return $value;
    }

    public function onGetStartDate(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->new_start_date)) {
            $value = $entity->new_start_date;
        }
        return $value;
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

    public function onGetPositionsHeld(Event $event, Entity $entity)
    {
        $value = '';
        // pr($entity);die;
        if (!empty($entity->previous_institution_staff_id)) {
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');

            if ($entity->has('previous_institution_staff')) {
                $institutionId = $entity->previous_institution_staff->institution_id;
            }

            if ($entity->has('user')) {
                $staffId = $entity->user->id;
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
                    $this->PreviousInstitutionStaff->aliasField('staff_id') => $staffId,
                    $this->PreviousInstitutionStaff->aliasField('staff_status_id') => $StaffStatuses->getIdByCode('ASSIGNED')
                ])
                ->order([$this->PreviousInstitutionStaff->aliasField('created') => 'DESC'])
                ->toArray();

            $positions = [];
            foreach ($staffEntity as $staff) {
                $positions[$staff->id] = $staff->_matchingData['Positions']->name;
            }

            if (!empty($positions)) {
                $value = implode(",",$positions);
            }
        }
        return $value;
    }

    public function onGetPositionStartDate(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_start_date')) {
            $value = $this->formatDate($entity->previous_start_date);
        }
        return $value;
    }


    public function onGetPositionEndDate(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_end_date')) {
            $value = $this->formatDate($entity->previous_end_date);
        }
        return $value;
    }

    // public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    // {
    //     if (in_array($action, ['add', 'edit', 'approve'])) {
    //         $entity = $attr['entity'];
    //         $attr['type'] = 'readonly';
    //         $attr['value'] = $entity->staff_id;
    //         $attr['attr']['value'] = $entity->user->name_with_id;
    //         return $attr;
    //     }
    // }

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

            if ($this->action == 'add') {
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
                $attr['onChangeReload'] = true;
            } else {

                $attr['type'] = 'readonly';
                //$attr['value'] = implode(" ", $options);
                $attr['attr']['value'] = implode(",", $options);
            }
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

                $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
                $Institutions = TableRegistry::get('Institution.Institutions');

                //restrict staff release between same type
                //TBC !!!!
                $restrictStaffTransferByType = $ConfigItems->value('restrict_staff_release_between_same_type');
                if ($restrictStaffTransferByType) {
                    if ($entity->has('institution_id')) {
                        $institutionId = $entity->institution_id;

                        $institutionTypeId = $Institutions->get($institutionId)->institution_type_id;

                        $conditions['institution_type_id'] = $institutionTypeId;
                    }
                }
                // end: restrict staff release between same type

                $options = $this->NewInstitutions->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->where($conditions)
                    ->order($this->NewInstitutions->aliasField('code'))
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
        //$this->request->data[$this->alias()]['transfer_type'] = $entity->transfer_type;

        // if (!empty($entity->previous_institution_staff_id)) {
        //     $this->request->data[$this->alias()]['positions_held'] = $entity->previous_institution_staff_id;
        // }
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Users', 'NewInstitutions', 'PreviousInstitutions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('previous_information_header', ['type' => 'section', 'title' => __('Release From')]);
        $this->field('staff_id', ['entity' => $entity]);
        $this->field('previous_institution_id', ['entity' => $entity]);
        $this->field('positions_held', ['type' => 'readonly', 'entity' => $entity]);

        // to populate current institution staff fields based on selected positions_held
        $FTE = $staffType = $startDate = $startDateFormatted = '';
        // if (isset($this->request->data[$this->alias()]['positions_held']) && !empty($this->request->data[$this->alias()]['positions_held'])) {
        //     $institutionStaffId = $this->request->data[$this->alias()]['positions_held'];
        //     $staffEntity = $this->PreviousInstitutionStaff->get($institutionStaffId, ['contain' => ['StaffTypes']]);
        //     if (!empty($staffEntity)) {
        //         $FTE = $this->fteOptions["$staffEntity->previous_FTE"];
        //         $staffType = $staffEntity->staff_type->name;
        //         $startDate = $staffEntity->start_date->format('Y-m-d');
        //         $startDateFormatted = $this->formatDate($staffEntity->start_date);
        //     }
        // }

        $this->field('FTE', ['type' => 'readonly', 'attr' => ['value' => $FTE]]);
        $this->field('staff_type_id', ['type' => 'readonly', 'attr' => ['value' => $staffType]]);

        $this->field('position_start_date');
        $this->field('position_end_date');

        $this->field('previous_start_date', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);
        $this->field('previous_FTE', ['type' => 'hidden']);
        $this->field('previous_staff_type_id', ['type' => 'hidden']);

        $this->field('new_information_header', ['type' => 'section', 'title' => __('Release To')]);
        $this->field('new_institution_id', ['entity' => $entity]);
        $this->field('new_start_date', ['entity' => $entity]);
        $this->field('new_end_date', ['entity' => $entity]);

        $this->field('new_institution_position_id', ['entity' => $entity]);

        $this->field('transfer_reasons_header', ['type' => 'section', 'title' => __('Other Information')]);
        $this->field('comment');
    }

    public function onUpdateFieldNewInstitutionPositionId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $options = [];
            if (!empty($request->data[$this->alias()]['new_institution_id']) && !empty($request->data[$this->alias()]['new_FTE']) && !empty($request->data[$this->alias()]['new_start_date'])) {
                $PositionsTable = TableRegistry::get('Institution.InstitutionPositions');

                $userId = $this->Auth->user('id');
                $isAdmin = $this->AccessControl->isAdmin();
                $activeStatusId = $this->Workflow->getStepsByModelCode($PositionsTable->registryAlias(), 'ACTIVE');

                $institutionId = $request->data[$this->alias()]['new_institution_id'];
                $fte = $request->data[$this->alias()]['new_FTE'];
                $startDate = $request->data[$this->alias()]['new_start_date'];
                $endDate = !empty($request->data[$this->alias()]['new_end_date']) ? $request->data[$this->alias()]['new_end_date'] : '';

                $options = $PositionsTable->getInstitutionPositions($userId, $isAdmin, $activeStatusId, $institutionId, $fte, $startDate, $endDate);
            }

            // need to specify select option for approve action
            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $options;
            return $attr;
        }
    }

    public function onUpdateFieldNewFTE(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            // need to specify select option for approve action
            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $this->fteOptions;
            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onUpdateFieldNewStaffTypeId(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['edit', 'approve'])) {
            $options = $this->NewStaffTypes->find('list')->toArray();

            // need to specify select option for approve action
            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $options;
            return $attr;
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        // redirect to view page of record after save
        $extra['redirect'][0] = 'view';
        $extra['redirect'][1] = $this->paramsEncode(['id' => $entity->id]);
    }
}
