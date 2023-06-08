<?php
namespace Institution\Model\Table;

use ArrayObject;
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
        $this->table('institution_staff_leave_archived');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        // POCOR-4047 to get staff profile data
        $this->addBehavior('Institution.StaffProfile');
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

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $allowOutAcademicYear = $ConfigItems->value('allow_out_academic_year');

        if ($allowOutAcademicYear == 1) {
            $validator
            ->add('date_to', 'ruleDateToInRange', [
                'rule' => ['DateToInRange'],
                'message' => __('Date to is greater than number of year range')
            ]);
        } else {
            $validator
            ->add('date_to', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id',[]]
            ]);
        }
        
        return $validator
            ->add('date_to', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'date_from', true]
            ])  
            ->add('date_from', 'ruleInAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id',[]]
            ])
            ->add('date_from', 'leavePeriodOverlap', [
                'rule' => ['noOverlappingStaffAttendance']
            ])
            ->allowEmpty('file_content');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStaff.afterDelete'] = 'institutionStaffAfterDelete';
        $events['Behavior.Historical.index.beforeQuery'] = 'indexHistoricalBeforeQuery';
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
        $this->addExtraButtons($extra);

    }
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        unset($buttons['edit']);
        return $buttons;
    }
    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
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

    public function getUserId()
    {
        $userId = null;
        if (!is_null($this->request->query('user_id'))) {
            $userId = $this->request->query('user_id');
        } else {
            $session = $this->request->session();
            if ($session->check('Staff.Staff.id')) {
                $userId = $session->read('Staff.Staff.id');
            }
        }

        return $userId;
    }

    private function addExtraButtons(ArrayObject $extra)
    {

        $toolbarButtons = $extra['toolbarButtons'];
        $this->addManualButton($toolbarButtons);

        $this->addBackButton($toolbarButtons);


    }

    /**
     * @param $toolbarButtons
     */
    private function addManualButton($toolbarButtons)
    {
        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Personal', 'Leave', 'Staff - Career');
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
            $customButtonName = 'back';
            $customButtonUrl = [
                                            'plugin' => 'Institution',
                                            'controller' => 'Institutions',
                                            'action' => 'StaffLeave',
            ];
            $customButtonLabel = '<i class="fa kd-back"></i>';
            $customButtonTitle = __('Back');
            $this->generateButton($toolbarButtons, $customButtonName, $customButtonTitle, $customButtonLabel, $customButtonUrl);
    }

    private function setInstitutionStaffIDs()
    {
        $institutionId = $staffId = null;
        $session = $this->controller->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }
        $staffId = $this->getStaffId();
        if (!$staffId) {
            $staffId = $this->Session->read('Institution.Staff.id');
        }
        $this->institutionId = $institutionId;
        $this->staffId = $staffId;
    }

    public function getStaffId()
    {
        $userId = null;
        if (!is_null($this->request->query('user_id'))) {
            $userId = $this->request->query('user_id');
        } else {
            $session = $this->request->session();
            if ($session->check('Staff.Staff.id')) {
                $userId = $session->read('Staff.Staff.id');
            }
        }

        return $userId;
    }
}
