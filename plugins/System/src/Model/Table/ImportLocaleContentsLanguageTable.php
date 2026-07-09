<?php

namespace System\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportLocaleContentsLanguageTable extends AppTable
{

    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import');

        // register the target table once
       // $this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $this->addBehavior('ControllerAction.FileUpload');
    }

    public function implementedEvents(): array
    {

        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportPopulateLanguages' => 'onImportPopulateLanguages',
            'Model.import.onImportPopulateLocaleContents' => 'onImportPopulateLocaleContents',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'
        ];
        $events = array_merge($events, $newEvent);

        return $events;
    }

     public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
          
        $toolbarButtons['back']['url'][0] = $toolbarButtons['back']['url']['action'];
        $toolbarButtons['back']['url']['action'] = 'LocaleContents/LocaleContents';
    }

    public function onImportPopulateLanguages(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel); // POCOR-8683 start
        $modelData = $lookedUpTable->find('all')
            ->select(['name', $lookupColumn])
                                ->order([$lookupModel.'.order'])
                                ;

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateLocaleContents(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel); // POCOR-8683 start
        $modelData = $lookedUpTable->find('all')
            ->select(['name', $lookupColumn])
                                ->order([$lookupModel.'.order'])
                                ;

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'select_file':
                return __('Select File To Import');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }



}






