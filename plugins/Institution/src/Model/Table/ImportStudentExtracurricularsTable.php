<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use DateTimeInterface;
use DateTime;


class ImportStudentExtracurricularsTable extends AppTable
{
    private $institutionId;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);
        $this->addBehavior('Import.Import', ['plugin'=>'Student', 'model'=>'Extracurriculars',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Students'] // POCOR-9896
        ]);
        $this->addBehavior('Institution.ImportStudent');
    }
    
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeAction($event) {
        $this->institutionId = !empty($this->request->getParam('institutionId')) ? $this->paramsDecode($this->request->getParam('institutionId'))['id'] : $this->request->getSession()->read('Institution.Institutions.id');
    }

    
    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $plugin = $toolbarButtons['back']['url']['plugin'];
        if ($plugin == 'Institution') {
            $toolbarButtons['back']['url']['action'] = 'Students';
        }
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona) 
    {
        $crumbTitle = $this->getHeader($this->getAlias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportPopulateAcademicPeriodsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
                if ($row->academic_period_level_id == 1) { //validate that only period level "year" will be shown
                    $date = $row->start_date;
                    $data[$columnOrder]['data'][] = [
                        $row->name,
                        $row->start_date->format('d/m/Y'),
                        $row->end_date->format('d/m/Y'),
                        $row->{$lookupColumn}
                    ];
                }
            }
        }
    }   

    public function onImportPopulateUsersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        unset($data[$columnOrder]);
    }
}
