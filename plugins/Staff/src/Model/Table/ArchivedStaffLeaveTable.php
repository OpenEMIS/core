<?php

namespace Staff\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\RecordNotFoundException;
use DatePeriod;
use DateInterval;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Log\Log;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use App\Model\Table\ControllerActionTable;


class ArchivedStaffLeaveTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $institutionId = null;
    private $staffId = null;

    public function initialize(array $config)

    {

        $config['Modified'] = false;
        $config['Created'] = false;
        $table_name = 'institution_staff_leave';
        $targetTableNameAndConnection = ArchiveConnections::getArchiveTableAndConnection($table_name);
        $targetTableName = $targetTableNameAndConnection[0];
        $targetTableConnection = $targetTableNameAndConnection[1];
        $remoteConnection = ConnectionManager::get($targetTableConnection);
        $this->connectionName = $targetTableConnection;
        $this->connection($remoteConnection);
        $this->table($targetTableName);
        parent::initialize($config);

//        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
//        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
//        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
//        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
//        $this->belongsTo('Assignees', ['className' => 'User.Users']);
//        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        // POCOR-4047 to get staff profile data

        $this->toggle('view', true);
        $this->toggle('edit', true);
        $this->toggle('delete', true);
        $this->toggle('remove', true);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $contentHeader = $this->controller->viewVars['contentHeader'];
        list($staffName, $module) = explode(' - ', $contentHeader);
        $module = __('Staff Leaves Archived');
        $contentHeader = $staffName . ' - ' . $module;
        $this->controller->set('contentHeader', $contentHeader);
        $this->controller->Navigation->substituteCrumb(__('Staff Leaves Archived'), $module);
        $this->setInstitutionStaffIDs();
    }


    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('start_time', ['visible' => false]);
        $this->field('end_time', ['visible' => false]);
        $this->field('time', ['after' => 'date_to']);
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }
        if (isset($extra['toolbarButtons']['edit'])) {
            unset($extra['toolbarButtons']['edit']);
        }
//        if (isset($extra['toolbarButtons']['view'])) {
//            unset($extra['toolbarButtons']['view']);
//        }
        $this->addExtraButtons($extra);
        $this->setInstitutionStaffIDs();

    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        unset($buttons['edit']);
        unset($buttons['view']);
        return $buttons;
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $this->setInstitutionStaffIDs();
        $staffId = $this->staffId;
        $institutionId = $this->institutionId;
        $where = [];
        if ($staffId) {
            $where[$this->aliasField('staff_id')] = $staffId;
        }
        if ($institutionId) {
            $where[$this->aliasField('institution_id')] = $institutionId;
        }

        $query->where($where);
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @return string
     * common proc to show related field in the index table
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetTime(Event $event, Entity $entity)
    {
        $time = "<i class='fa fa-minus'></i>";
        $isFullDay = $entity->full_day;
        if ($isFullDay == 0) {
            $startTime = $entity->start_time;
            $endTime = $entity->end_time;
            $time = $this->formatTime($startTime) . ' - ' . $this->formatTime($endTime);
        }
        return $time;
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @return string
     * common proc to show related field in the index table
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetFullDay(Event $event, Entity $entity)
    {
        $fullDay = "<i class='fa fa-times'></i>";
        $isFullDay = $entity->full_day;
        if ($isFullDay == 1) {
            $fullDay = "<i class='fa fa-check'></i>";
        }
        return $fullDay;
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @return string
     * common proc to show related field in the index table
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetComments(Event $event, Entity $entity)
    {
        $comments = "<i class='fa fa-minus'></i>";
        $isComments = $entity->comments;
        if ($isComments != "") {
            $comments = $isComments;
        }
        return $comments;
    }


    /**
     * common proc to show related field in the index table
     * @param $tableName
     * @param $relatedField
     * @return string
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function getRelatedName($tableName, $relatedField)
    {
        if(!$relatedField){
            return "";
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            $name = strval($related->name);
            return $name;
        } catch (RecordNotFoundException $e) {
            return $relatedField;
        }
    }


    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @return string
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function getRelatedNameWithId($tableName, $relatedField)
    {
        if(!$relatedField){
            return "";
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            $name = strval($related->nameWithId);
            return $name;
        } catch (RecordNotFoundException $e) {
            return $relatedField;
        }
        return $name;
    }


    /**
     * @param Event $event
     * @param Entity $entity
     * @return string
     * common proc to show related field in the index table
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetStaffId(Event $event, Entity $entity)
    {
        $tableName = 'User.Users';
        $relatedField = $entity->staff_id;
        $name = $this->getRelatedName($tableName, $relatedField);
        return $name;
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @return string
     * common proc to show related field in the index table
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetAssigneeId(Event $event, Entity $entity)
    {
        $tableName = 'User.Users';
        $relatedField = $entity->assignee_id;
        $name = $this->getRelatedName($tableName, $relatedField);
        return $name;
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @return string
     * common proc to show related field in the index table
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetStatusId(Event $event, Entity $entity)
    {
        $tableName = 'Workflow.WorkflowSteps';
        $relatedField = $entity->status_id;
        $name = $this->getRelatedName($tableName, $relatedField);
        return '<span class="status highlight">' . $name . '</span>';
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @return string
     * common proc to show related field in the index table
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetStaffLeaveTypeId(Event $event, Entity $entity)
    {
        $tableName = 'Staff.StaffLeaveTypes';
        $relatedField = $entity->staff_leave_type_id;
        $name = $this->getRelatedName($tableName, $relatedField);
        return $name;
    }

    /**
     * @param Event $event
     * @param Entity $entity
     * @return string
     * common proc to show related field in the index table
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        $tableName = 'AcademicPeriod.AcademicPeriods';
        $relatedField = $entity->academic_period_id;
        $name = $this->getRelatedName($tableName, $relatedField);
        return $name;
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->staffId;
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StaffLeave');
    }

    /**
     * common proc to add extra buttons, to call in indexBeforeAction
     * @param ArrayObject $extra
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function addExtraButtons(ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $this->addManualButton($toolbarButtons);

        $this->addBackButton($toolbarButtons);
    }

    /**
     * common proc to add a manual button
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * @param $toolbarButtons
     */
    private function addManualButton($toolbarButtons)
    {
        // Start POCOR-5188
        $options  = ['Personal', 'Leave', 'Staff - Career'];
        $is_manual_exist = $this->getManualUrl(...$options);
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $customButtonName = 'help';
            $customButtonUrl = $is_manual_exist['url'];
            $customButtonLabel = '<i class="fa fa-question-circle"></i>';
            $customButtonTitle = __('Help');
            $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl, $btnAttr);
        }
    }

    /**
     * common proc to generate button
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     * @param ArrayObject $toolbarButtons
     * @param $name
     * @param $title
     * @param $label
     * @param $url
     * @param null $btnAttr
     */
    private function generateButton(ArrayObject $toolbarButtons, $name, $title, $label, $url, $btnAttr = null)
    {
        if (!$btnAttr) {
            $btnAttr = $this->getButtonAttr();
        }
        $customButton = [];
        if (array_key_exists('_ext', $url)) {
            unset($customButton['url']['_ext']);
        }
        if (array_key_exists('pass', $url)) {
            unset($customButton['url']['pass']);
        }
        if (array_key_exists('paging', $url)) {
            unset($customButton['url']['paging']);
        }
        if (array_key_exists('filter', $url)) {
            unset($customButton['url']['filter']);
        }
        $customButton['type'] = 'button';
        $customButton['attr'] = $btnAttr;
        $customButton['attr']['title'] = $title;
        $customButton['label'] = $label;
        $customButton['url'] = $url;
        $toolbarButtons[$name] = $customButton;
    }

    private function addBackButton($toolbarButtons)
    {
        $customButtonUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StaffLeave',
            'index',
            'user_id' => $this->staffId
        ];

        if (is_null($this->institutionId)) {
            $customButtonUrl = [
                'plugin' => 'Directory',
                'controller' => 'Directories',
                'action' => 'StaffLeave',
                'index',
                'user_id' => $this->staffId
            ];
        }
        $customButtonName = 'back';
        $customButtonLabel = '<i class="fa kd-back"></i>';
        $customButtonTitle = __('Back');
        $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl);
    }

    /**
     * common proc to get/set main variables to use further
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function setInstitutionStaffIDs()
    {
//        $this->log('setInstitutionStaffIDs', 'debug');
//        $this->log($this->staffId);
//        $this->log($this->institutionId);
        $institutionId = $staffId = null;
        $session = $this->controller->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }
        if (!is_null($this->request->query('user_id'))) {
            $staffId = $this->request->query('user_id');
//            $this->log('$this->request->query(\'user_id\')', 'debug');
//            $this->log($staffId, 'debug');
        }
        if (!is_null($this->request->query('staff_id'))) {
            $staffId = $this->request->query('staff_id');
//            $this->log('$this->request->query(\'staff_id\')', 'debug');
//            $this->log($staffId, 'debug');
        }
        if ($session->check('Institution.Staff.id')) {
            if(is_numeric($session->read('Institution.Staff.id'))){
            $staffId = $session->read('Institution.Staff.id');
//            $this->log('$session->read(\'Institution.Staff.id\')', 'debug');
//            $this->log($staffId, 'debug');
            }
        }
        if ($session->check('Staff.Staff.id')) {
            if(is_numeric($session->read('Staff.Staff.id'))){
            $staffId = $session->read('Staff.Staff.id');
//            $this->log('$session->read(\'Staff.Staff.id\')', 'debug');
//            $this->log($staffId, 'debug');
            }
        }
//        if ($session->check('Directory.Staff.id')) {
//            $staffId = $session->read('Directory.Staff.id');
//        }
        $this->institutionId = $institutionId;
        $this->staffId = $staffId;
    }

}
