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
        $this->addBehavior('Import.Import', [
            'plugin' => 'System',
            'model' => 'LocaleContentsLanguage'
        ]);
        $this->addBehavior('ControllerAction.FileUpload');
        $this->LocaleContentsLanguage = TableRegistry::getTableLocator()->get('System.LocaleContentsLanguage');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportPopulateLocalesData' => 'onImportPopulateLocalesData',
           // 'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
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

    public function onImportPopulateLocalesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel); 
        $modelData = $lookedUpTable->find('all')
            ->select(['id','iso','name', $lookupColumn])
                                //->order([$lookupModel.'.order'])
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

    public function onImportCheckUniquennn(
    EventInterface $event,
    $sheet,
    $row,
    $columns,
    ArrayObject $tempRow,
    ArrayObject $importedUniqueCodes,
    ArrayObject $rowInvalidCodeCols)
    {
        $columns = new Collection($columns);

        // Translation(Label) column
        $translationColumn = $columns->filter(function ($value) {
            return $value == 'label';
        });

        $translationIndex = key($translationColumn->toArray());
        $translation = trim($sheet->getCellByColumnAndRow($translationIndex, $row)->getValue());
        echo "<pre>"; print_r($translation); die;
        // Locale column
        $localeColumn = $columns->filter(function ($value) {
            return $value == 'locale_id';
        });
        $localeContentsLanguage = TableRegistry::getTableLocator()->get('System.LocaleContentsLanguage');
        $localeIndex = key($localeColumn->toArray());
        $localeId = trim($sheet->getCellByColumnAndRow($localeIndex, $row)->getValue());

        $entity = $localeContentsLanguage->find()
            ->where([
                'translation' => $translation,
                'locale_id' => $localeId
            ])
            ->enableHydration(false)
            ->first();

        if ($entity) {
            $tempRow['entity'] = $entity;    // Update existing record
        } else {
            $tempRow['entity'] = $localeContentsLanguage->newEntity(); // Insert new record
        }
    }

}






