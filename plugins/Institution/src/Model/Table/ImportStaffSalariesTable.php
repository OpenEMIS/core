<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTimeInterface;
use PHPExcel_Worksheet;

class ImportStaffSalariesTable extends AppTable
{
    use OptionsTrait;

    public $table = 'import_mapping';

    private $institutionId;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        //POCOR-5182 start 
        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'Salaries']);
        $this->addBehavior('Institution.ImportStaff');
        //POCOR-5182 end
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons',
            'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $plugin = $toolbarButtons['back']['url']['plugin'];
        if ($plugin == 'Institution') {
            $toolbarButtons['back']['url']['action'] = 'Staff, index';
        }
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) 
    {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if (isset($buttons[1])) {
            $buttons[1]['url'] = $this->ControllerAction->url('index');
            $buttons[1]['url']['action'] = 'Staff';
        }
    }

    public function onImportPopulateSalaryAdditionTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $result = $lookedUpTable
            ->find('all')
            ->select([
                $lookedUpTable->aliasField('name'),
                $lookedUpTable->aliasField($lookupColumn)
            ])
            ->order([
                $lookedUpTable->aliasField('name')
            ])
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        if (!$result->isEmpty()) {
            $modelData = $result->toArray();
            foreach ($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateSalaryDeductionTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $result = $lookedUpTable
            ->find('all')
            ->select([
                $lookedUpTable->aliasField('name'),
                $lookedUpTable->aliasField($lookupColumn)
            ])
            ->order([
                $lookedUpTable->aliasField('name')
            ])
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        if (!$result->isEmpty()) {
            $modelData = $result->toArray();
            foreach ($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }
}    
