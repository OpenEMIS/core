<?php
namespace App\Controller;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class CalendarsController extends PageController
{
    private $academicPeriodOptions = [];
    private $calendarTypeOptions = [];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('CalendarTypes');
        $this->loadModel('CalendarEventDates');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
        $this->loadModel('Calendars');
        $this->Page->loadElementsFromTable($this->Calendars);
        $this->Page->disable(['search']); // to disable the search function
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderStartDate'] = 'onRenderStartDate';
        $event['Controller.Page.onRenderEndDate'] = 'onRenderEndDate';
        $event['Controller.Page.onRenderCalendarTypeId'] = 'onRenderCalendarTypeId';
        $event['Controller.Page.getEntityDisabledActions'] = 'getEntityDisabledActions';

        return $event;
    }

    public function onRenderCalendarTypeId(Event $event, Entity $entity, PageElement $element)
    {
        if ($this->Page->is(['view'])) {
            return __($entity->calendar_type->name);
        }
    }

    public function onRenderStartDate(Event $event, Entity $entity, PageElement $element)
    {
        $calendarEventId = $entity->id;
        $query = $this->CalendarEventDates->find();

        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $format = $ConfigItem->value('date_format');

        if ($this->Page->is(['index', 'view', 'delete'])) {
            // for translation
            $entity->calendar_type->name = __($entity->calendar_type->name);
            $calendarEventDate = $query
                ->where([
                    $this->CalendarEventDates->aliasField('calendar_event_id') => $calendarEventId
                ])
                ->hydrate(false)
                ->toArray()
            ;

            $startDate = min($calendarEventDate)['date']->format($format);

            return $startDate;
        }
    }

    public function onRenderEndDate(Event $event, Entity $entity, PageElement $element)
    {
        $calendarEventId = $entity->id;
        $query = $this->CalendarEventDates->find();

        $ConfigItem = TableRegistry::get('Configuration.ConfigItems');
        $format = $ConfigItem->value('date_format');

        if ($this->Page->is(['index', 'view', 'delete'])) {
            $calendarEventDate = $query
                ->where([
                    $this->CalendarEventDates->aliasField('calendar_event_id') => $calendarEventId
                ])
                ->hydrate(false)
                ->toArray()
            ;

            $endDate = max($calendarEventDate)['date']->format($format);

            return $endDate;
        }
    }

    public function getEntityDisabledActions(Event $event, $entity)
    {
        $disabledActions = [];
        $institutionId = $this->Page->getQueryString('institution_id');
        $entityInstitutionId = $entity->institution_id;

        if ($institutionId != $entityInstitutionId) {
            $disabledActions = ['edit', 'delete'];
        }

        return $disabledActions;
    }

    private function setCalendarTypeOptions()
    {
        $plugin = $this->plugin;

        if ($plugin == 'Institution') {
            $this->calendarTypeOptions = $this->CalendarTypes->getInstitutionCalendarTypeList();
        } else {
            $this->calendarTypeOptions = $this->CalendarTypes->getAdministrationCalendarTypeList();
        }
    }

    public function beforeFilter(Event $event)
    {
        // set institution_id
        $this->setInstitutionId();

        parent::beforeFilter($event);

        // set Breadcrumb
        $this->setBreadCrumb();

        $page = $this->Page;

        // set header
        $page->setHeader(__('Calendar'));

        // set field
        $page->get('institution_id')->setControlType('hidden');
        $page->get('calendar_type_id')->setLabel('Type');

        // will get the consolidate date
        $page->addNew('start_date');
        $page->addNew('end_date');

        // set field order
        $page->move('name')->first();
        $page->move('calendar_type_id')->after('name');
        $page->move('academic_period_id')->after('calendar_type_id');
        $page->move('start_date')->after('academic_period_id');
        $page->move('end_date')->after('start_date');

        // set options
        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $this->setCalendarTypeOptions();
    }

    public function index()
    {
        $page = $this->Page;

        // set field
        $page->exclude(['comment', 'academic_period_id', 'institution_id']);

        // set field order
        $page->move('calendar_type_id')->first();
        $page->move('name')->after('calendar_type_id');

        // set filter academic period
        $page->addFilter('academic_period_id')
            ->setOptions($this->academicPeriodOptions);

        // set queryString
        $academicPeriodId = !empty($requestQuery['querystring']) ? $this->Page->decode($requestQuery['querystring']): $this->AcademicPeriods->getCurrent();
        $page->setQueryString('academic_period_id', $academicPeriodId);

        parent::index();
    }

    public function view($id)
    {
        parent::view($id);
        $entity = $this->Page->getData();
        $institutionId = $this->Page->getQueryString('institution_id');
        $entityInstitutionId = $entity->institution_id;

        if ($institutionId != $entityInstitutionId) {
            $this->Page->disable(['edit', 'delete']); // to disable the edit and delete
        }
    }

    public function add()
    {
        $this->addEdit();
        parent::add();

        $page = $this->Page;

        if ($this->request->is(['get'])) {
            // set default academic period to current year
            $academicPeriodId = !is_null($page->getQueryString('academic_period_id')) ? $page->getQueryString('academic_period_id') : $this->AcademicPeriods->getCurrent();
            $page->get('academic_period_id')->setValue($academicPeriodId);
        } elseif ($this->request->is(['post', 'put'])) {
            $entity = $page->getData();
            $error = $entity->errors();

            if (empty($error)) {
                $startDate = new Date($entity->start_date);
                $endDate = new Date($entity->end_date);
                $endDate = $endDate->modify('+1 day');
                $interval = new DateInterval('P1D');
                $calendarEventId = $entity->id;

                $datePeriod = new DatePeriod($startDate, $interval, $endDate);

                foreach ($datePeriod as $date) {
                    $dateEntity = $this->CalendarEventDates->newEntity([
                        'calendar_event_id' => $calendarEventId,
                        'date' => $date
                    ]);
                    $this->CalendarEventDates->save($dateEntity);
                }
            }
        }
    }

    public function edit($id)
    {
        $this->addEdit();
        parent::edit($id);

        $page = $this->Page;
        $entity = $page->getData();

        if ($this->request->is(['get'])) {
            $dateData = $entity->calendar_event_dates;
            $startDate = min($dateData)['date']->format('d-m-Y');
            $endDate = max($dateData)['date']->format('d-m-Y');

            $entity->start_date = $startDate;
            $entity->end_date = $endDate;
        } elseif ($this->request->is(['post', 'put'])) {
            $error = $entity->errors();

            if (empty($error)) {
                if ($entity->has('start_date') && $entity->has('end_date')) {
                    $startDate = new Date($entity->start_date);
                    $endDate = new Date($entity->end_date);
                } else {
                    $dateData = $entity->calendar_event_dates;
                    $startDate = min($dateData)['date'];
                    $endDate = max($dateData)['date'];
                }

                $endDate = $endDate->modify('+1 day');
                $interval = new DateInterval('P1D');
                $calendarEventId = $entity->id;

                $datePeriod = new DatePeriod($startDate, $interval, $endDate);

                // delete all the date and re add the date
                $this->CalendarEventDates->deleteAll([
                    'calendar_event_id' => $calendarEventId
                ]);

                foreach ($datePeriod as $date) {
                    $dateEntity = $this->CalendarEventDates->newEntity([
                        'calendar_event_id' => $calendarEventId,
                        'date' => $date
                    ]);
                    $this->CalendarEventDates->save($dateEntity);
                }
            }
        }
    }

    private function addEdit()
    {
        $page = $this->Page;

        // set institution id
        $this->setInstitutionId();

        // set academic
        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions, false)
        ;

        // set type
        $page->get('calendar_type_id')
            ->setControlType('select')
            ->setOptions($this->calendarTypeOptions, false)
        ;

        // will get the consolidate date
        $page->get('start_date')
            ->setControlType('date')
            ->setRequired(true)
        ;
        $page->addNew('end_date')
            ->setControlType('date')
            ->setRequired(true)
        ;
    }

    private function setInstitutionId()
    {
        $page = $this->Page;
        $plugin = $this->plugin;

        if ($plugin == 'Institution') {
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');

            $page->setQueryString('institution_id', $institutionId);
            $page->get('institution_id')->setValue($institutionId);
        } else {
            // non institution (administration)
            $page->setQueryString('institution_id', -1);
            $page->get('institution_id')->setValue(-1);
        }
    }

    private function setBreadCrumb()
    {
        $page = $this->Page;
        $plugin = $this->plugin;

        if ($plugin == 'Institution') {
            $session = $this->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $institutionName = $session->read('Institution.Institutions.name');
            $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

            $page->addCrumb('Institutions', [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Institutions',
                'index'
            ]);
            $page->addCrumb($institutionName, [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'dashboard',
                'institutionId' => $encodedInstitutionId,
                $encodedInstitutionId
            ]);
        }
        $page->addCrumb(__('Calendar'));
    }
}
