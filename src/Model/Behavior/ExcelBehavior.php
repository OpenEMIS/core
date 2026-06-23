<?php
namespace App\Model\Behavior;

use Cake\ORM\TableRegistry; //POCOR-6538
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;
use Cake\I18n\I18n;
use Cake\Utility\Hash;
use XLSXWriter;

// Events
// public function onExcelBeforeGenerate(EventInterface $event, ArrayObject $settings) {}
// public function onExcelGenerate(EventInterface $event, $writer, ArrayObject $settings) {}
// public function onExcelGenerateComplete(EventInterface $event, ArrayObject $settings) {}
// public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query) {}
// public function onExcelStartSheet(EventInterface $event, ArrayObject $settings, $totalCount) {}
// public function onExcelEndSheet(EventInterface $event, ArrayObject $settings, $totalProcessed) {}
// public function onExcelGetLabel(EventInterface $event, $column) {}

class ExcelBehavior extends Behavior
{
    use EventTrait;

    private $events = [];

    protected $_defaultConfig = [
        'folder' => 'export',
        'default_excludes' => ['modified_user_id', 'modified', 'created', 'created_user_id', 'password'],
        'excludes' => [],
        'limit' => 1000000,//POCOR-6603
        'pages' => [],
        'autoFields' => true,
        'orientation' => 'landscape', // or portrait
        'sheet_limit' =>  1000000, // 1 mil rows and header row
        'auto_contain' => true
    ];

    public function initialize(array $config): void
    {
        $this->setConfig('excludes', array_merge($this->getConfig('default_excludes'), $this->getConfig('excludes')));
        if (!isset($config['filename'])) {
            $this->setConfig('filename', $this->_table->getAlias());
        }
        $folder = WWW_ROOT . $this->getConfig('folder');

        if (!file_exists($folder)) {
            umask(0);
            mkdir($folder, 0777);
        } else {
            // $delete = true;
            // if (isset($settings['delete']) &&  $settings['delete'] == false) {
            //  $delete = false;
            // }
            // if ($delete) {
            //  $this->deleteOldFiles($folder, $format);
            // }
        }
        $pages = $this->getConfig('pages');
        if ($pages !== false && empty($pages)) {
            $this->setConfig('pages', ['index', 'view']);
        }
    }

    private function eventMap($method)
    {
        $exists = false;
        if (in_array($method, $this->events)) {
            $exists = true;
        } else {
            $this->events[] = $method;
        }
        return $exists;
    }

    public function excel($id = 0)
    {
        $ids = empty($id) ? [] : $this->_table->paramsDecode($id);
        $this->generateXLXS($ids);
    }

    public function excelV4(EventInterface $mainEvent, ArrayObject $extra)
    {
        $id = 0;
        $break = false;
        $action = $this->_table->action;
        $pass = $this->_table->request->getParam('pass');
        if (in_array($action, $pass)) {
            unset($pass[array_search($action, $pass)]);
            $pass = array_values($pass);
        }
        if (isset($pass[0])) {
            $id = $pass[0];
        }
        $ids = empty($id) ? [] : $this->_table->paramsDecode($id);
        $this->generateXLXS($ids);
        return true;
    }

    private function eventKey($key)
    {
        return 'Model.excel.' . $key;
    }

    public function generateXLXS($settings = [])
    {
        set_time_limit(0); //POCOR-7268 starts
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '9600'); //POCOR-7268 ends
        $_settings = [
            'file' => $this->getConfig('filename') . '_' . date('Ymd') . 'T' . date('His') . '.xlsx',
            'path' => WWW_ROOT . $this->getConfig('folder') . DS,
            'download' => true,
            'purge' => true
        ];
        $_settings = new ArrayObject(array_merge($_settings, $settings));

        $this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeGenerate'), 'onExcelBeforeGenerate', [$_settings]);

        $writer = new XLSXWriter();
        $excel = $this;

        $generate = function ($settings) {
            $this->generate($settings);
        };

        $_settings['writer'] = $writer;

        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerate'), 'onExcelGenerate', [$_settings]);
        if ($event->isStopped()) {
            return $event->getResult();
        }
        if (is_callable($event->getResult())) {
            $generate = $event->getResult();
        }

        //POCOR-9567: start - open CSV companion file before generate so rows can be written in parallel
        $csvFilepath = $_settings['path'] . preg_replace('/\.xlsx$/i', '.csv', $_settings['file']);
        $csvHandle = fopen($csvFilepath, 'w');
        $_settings['csv_handle'] = $csvHandle;
        //POCOR-9567: end

        $generate($_settings);

        $filepath = $_settings['path'] . $_settings['file'];
        $_settings['file_path'] = $filepath;
        $writer->writeToFile($_settings['file_path']);

        //POCOR-9567: start - close CSV companion file after xlsx write completes
        if (isset($_settings['csv_handle']) && is_resource($_settings['csv_handle'])) {
            fclose($_settings['csv_handle']);
        }
        //POCOR-9567: end

        $this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerateComplete'), 'onExcelGenerateComplete', [$_settings]);

        if ($_settings['download']) {
            $this->download($filepath);
        }

        if ($_settings['purge']) {
            $this->purge($filepath);
        }
        return $_settings;
    }

    public function generate($settings = [])
    {
        set_time_limit(0); //POCOR-7268 starts
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '9600'); //POCOR-7268 ends
        $writer = $settings['writer'];
        $sheets = new ArrayObject();
        // Event to get the sheets. If no sheet is specified, it will be by default one sheet
        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeStart'), 'onExcelBeforeStart', [$settings, $sheets], true);

        if (count($sheets->getArrayCopy())==0) {
            $sheets[] = [
                'name' => $this->_table->getAlias(),
                'table' => $this->_table,
                'query' => $this->_table->find(),
            ];
        }

        $sheetNameArr = [];
        foreach ($sheets as $sheet) {
            $table = $sheet['table'];
            // sheet info added to settings to avoid adding more parameters to event
            $settings['sheet'] = $sheet;
            $this->getFields($table, $settings);
            $fields = $settings['sheet']['fields'];

            $footer = $this->getFooter();
            $query = $sheet['query'];

            $this->dispatchEvent($table, $this->eventKey('onExcelBeforeQuery'), 'onExcelBeforeQuery', [$settings, $query], true);
            $sheetName = $sheet['name'];

            // Check to make sure the string length does not exceed 31 characters
            $sheetName = (strlen($sheetName) > 31) ? substr($sheetName, 0, 27).'....' : $sheetName;

            // Check to make sure that no two sheets has the same name
            $counter = 1;
            $initialLength = 0;
            while (in_array($sheetName, $sheetNameArr)) {
                if ($counter > 1) {
                    $sheetName = substr($sheetName, 0, $initialLength);
                } else {
                    $initialLength = strlen($sheetName);
                }
                if (strlen($sheetName) > 23) {
                    $sheetName = substr($sheetName, 0, 23).'('.$counter++.')';
                } else {
                    $sheetName = $sheetName.'('.$counter++.')';
                }
            }
            $sheetNameArr[] = $sheetName;
            $baseSheetName = $sheetName;

            // if the primary key of the record is given, only generate that record
            //POCOR-8484 starts
            if(isset($this->_table->action) && !empty($this->_table->action)){
                $action = $this->_table->action;
                if($action != 'excel') {
                    if (isset($settings['id'])) {
                        $id = $settings['id'];
                        if ($id != 0) {
                            $primaryKey = $table->getPrimaryKey();
                            $query->where([$table->aliasField($primaryKey) => $id]);
                        }
                    }
                }//POCOR-8484 ends
                //POCOR-8627 Start
                if($settings['sheet']['name'] == 'StaffAppraisals' && $action == 'excel' && isset($settings['id']) && !empty(isset($settings['id']))) {
                    $id = $settings['id'];
                    $primaryKey = $table->getPrimaryKey();
                    $query->where([$table->aliasField($primaryKey) => $id]);
                }
                //POCOR-8627 End
            //POCOR-8515 starts
            }else{
                if (isset($settings['id'])) {
                    $id = $settings['id'];
                    if ($id != 0) {
                        $primaryKey = $table->getPrimaryKey();
                        $query->where([$table->aliasField($primaryKey) => $id]);
                    }
                }
            }//POCOR-8515 ends

            if ($this->getConfig('auto_contain')) {
                $this->contain($query, $fields, $table);
            }

            // To auto include the default fields. Using select will turn off autoFields by default
            // This is set so that the containable data will still be in the array.
            $autoFields = $this->getConfig('autoFields');

            if (!isset($autoFields) || $autoFields == true) {
                $query->enableAutoFields(true);
            }

            $count = $query->count();
            $rowCount = 0;
            $sheetCount = 1;
            $sheetRowCount = 0;
            $percentCount = intval($count / 100);
            $pages = ceil($count / $this->getConfig('limit'));

            // Debugging
            //$pages = 1; //comment this in POCOR-8755

            if (isset($sheet['orientation'])) {
                if ($sheet['orientation'] == 'landscape') {
                    $this->setConfig('orientation', 'landscape');
                } else {
                    $this->setConfig('orientation', 'portrait');
                }
            }elseif ($count == 1) {
                $params = json_decode($settings['process']->params, true); //POCOR-9731
                if (isset($params['feature']) && str_contains($params['feature'], 'Report')) {
                    $this->setConfig('orientation', 'landscape');
                }else{
                    $this->setConfig('orientation', 'portrait');
                } 
            }

            $this->dispatchEvent($table, $this->eventKey('onExcelStartSheet'), 'onExcelStartSheet', [$settings, $count], true);
            $this->onEvent($table, $this->eventKey('onExcelBeforeWrite'), 'onExcelBeforeWrite');
            if ($this->getConfig('orientation') == 'landscape') {
                $headerRow = [];
                $headerStyle = [];
                $headerFormat = [];

                // Handling of Group field for merging cells for first 2 row
                $groupList = Hash::extract($fields, '{n}.group');
                $hasGroupRow = (!empty($groupList));

                if ($hasGroupRow) {
                    $subjectsHeaderRow = [];
                    $subjectsColWidth = [];
                    $groupStartingIndex = 0;
                    $groupName = '';
                    $subjectHeaderstyle = ['halign' => 'center'];

                    foreach ($fields as $index => $attr) {
                        $subjectsHeaderRow[$index] = "";

                        if (isset($attr['group'])) {
                            if ($groupName !== $attr['group']) {
                                $groupStartingIndex = $index;
                                $groupName = $attr['group'];
                            }

                            $groupKey = $groupName . $groupStartingIndex;

                            if (!isset($subjectsColWidth[$groupKey])) {
                                $subjectsColWidth[$groupKey] = [];
                                $subjectsColWidth[$groupKey]['start_col'] = $index;
                                $subjectsHeaderRow[$index]  = $attr['group'];
                            }

                            $subjectsColWidth[$groupKey]['end_col'] = $index;

                        } else {
                            $groupName = '';
                        }
                    }

                    $writer->writeSheetRow($sheetName, $subjectsHeaderRow, $subjectHeaderstyle);

                    foreach ($subjectsColWidth as $obj) {
                        $writer->markMergedCell($sheetName, $start_row=0, $start_col=$obj['start_col'], $end_row=0, $end_col=$obj['end_col']);
                    }
                }
                // End of handling of Group field for merging cells

                foreach ($fields as $attr) {
                    $headerRow[] = $attr['label'];
                    $headerStyle[] = isset($attr['style']) ? $attr['style'] : [];
                    $headerFormat[] = isset($attr['formatting']) ? $attr['formatting'] : 'GENERAL';
                }

                // Any additional custom headers that require to be appended on the right side of the sheet
                // Header column count must be more than the additional data columns
                if (isset($sheet['additionalHeader'])) {
                    $headerRow = array_merge($headerRow, $sheet['additionalHeader']);
                }

                $writer->writeSheetHeader($sheetName, $headerFormat, true);
                $writer->writeSheetRow($sheetName, $headerRow, $headerStyle);
                //POCOR-9567: write header row to companion CSV
                if (isset($settings['csv_handle']) && is_resource($settings['csv_handle'])) {
                    fputcsv($settings['csv_handle'], $headerRow);
                }
                //POCOR-9567: end

                $this->dispatchEvent($table, $this->eventKey('onExcelAfterHeader'), 'onExcelAfterHeader', [$settings], true);

                // process every page based on the limit
                for ($pageNo=0; $pageNo<$pages; $pageNo++) {
                    $resultSet = $query
                    ->limit($this->getConfig('limit'))
                    ->page($pageNo+1)
                    ->all();

                    // Data to be appended on the right of spreadsheet
                    $additionalRows = [];
                    if (isset($sheet['additionalData'])) {
                        $additionalRows = $sheet['additionalData'];
                    }

                    // process each row based on the result set
                    foreach ($resultSet as $entity) {
                        if ($sheetRowCount >= $this->getConfig('sheet_limit')) {
                            $sheetCount++;
                            $sheetName = $baseSheetName . '_' . $sheetCount;

                            // rewrite header into new sheet
                            $writer->writeSheetRow($sheetName, $headerRow, $headerStyle);

                            $sheetRowCount= 0;
                        }

                        $settings['entity'] = $entity;

                        $row = [];
                        $rowStyle = [];
                        foreach ($fields as $attr) {
                            $rowDataWithStyle = $this->getValue($entity, $table, $attr);
                            $row[] = $rowDataWithStyle['rowData'];
                            $rowStyle[] = $rowDataWithStyle['style'];
                        }

                        $sheetRowCount++;
                        $rowCount++;
                        $event = $this->dispatchEvent($table, $this->eventKey('onExcelBeforeWrite'), null, [$settings, $rowCount, $percentCount]);
                        if (!$event->getResult()) {
                            $writer->writeSheetRow($sheetName, $row, $rowStyle);
                            //POCOR-9567: write data row to companion CSV
                            if (isset($settings['csv_handle']) && is_resource($settings['csv_handle'])) {
                                fputcsv($settings['csv_handle'], $row);
                            }
                            //POCOR-9567: end
                        }
                    }
                }
            } else {
                $entity = $query->first();
                foreach ($fields as $attr) {
                    $row = [$attr['label']];
                    $rowStyle = [[]];
                    $rowDataWithStyle = $this->getValue($entity, $table, $attr);
                    $row[] = $rowDataWithStyle['rowData'];
                    $rowStyle[] = $rowDataWithStyle['style'];
                    $writer->writeSheetRow($sheetName, $row, $rowStyle);
                }

                // Any additional custom headers that require to be appended on the left column of the sheet
                $additionalHeader = [];
                if (isset($sheet['additionalHeader'])) {
                    $additionalHeader = $sheet['additionalHeader'];
                }
                // Data to be appended on the right column of spreadsheet
                $additionalRows = [];
                if (isset($sheet['additionalData'])) {
                    $additionalRows = $sheet['additionalData'];
                }

                for ($i = 0; $i < count($additionalHeader); $i++) {
                    $row = [$additionalHeader[$i]];
                    $row[] = $additionalRows[$i];
                    $rowStyle = [[], []];
                    $writer->writeSheetRow($sheetName, $row, $rowStyle);
                }
                $rowCount++;
            }
            $writer->writeSheetRow($sheetName, ['']);
            $writer->writeSheetRow($sheetName, $footer);
            $this->dispatchEvent($table, $this->eventKey('onExcelEndSheet'), 'onExcelEndSheet', [$settings, $rowCount], true);
        }
    }

    private function getFields($table, $settings)
    {
        $schema = $table->getSchema();
        $columns = $schema->columns();
        $excludes = $this->getConfig('excludes');

        if (!is_array($table->getPrimaryKey())) { //if not composite key
            $excludes[] = $table->getPrimaryKey();
        }

        $fields = new ArrayObject();
        $module = $table->getAlias();
        $language = I18n::getLocale();
        $excludedTypes = ['binary'];
        $columns = array_diff($columns, $excludes);

        foreach ($columns as $col) {
            $field = $schema->getColumn($col);
            if (!in_array($field['type'], $excludedTypes)) {
                $label = $table->aliasField($col);

                $event = $this->dispatchEvent($table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $col, $language], true);
                if (strlen($event->getResult())) {
                    $label = $event->getResult();
                }

                $fields[] = [
                    'key' => $table->aliasField($col),
                    'field' => $col,
                    'type' => $field['type'],
                    'label' => $label,
                    'style' => [],
                    'formatting' => 'GENERAL'
                ];
            }
        }
        // Event to add or modify the fields to fetch from the table
        $event = $this->dispatchEvent($table, $this->eventKey('onExcelUpdateFields'), 'onExcelUpdateFields', [$settings, $fields], true);

        $newFields = [];
        foreach ($fields->getArrayCopy() as $field) {
            if (empty($field['label'])) {
                $key = explode('.', $field['key']);
                $module = $key[0];
                $column = $key[1];
                // Redispatch get label
                $event = $this->dispatchEvent($table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $column, $language], true);
                if (strlen($event->getResult())) {
                    $field['label'] = $event->getResult();
                }
            }
            $newFields[] = $field;
        }

        // Replace the ArrayObject with the new fields
        $fields->exchangeArray($newFields);

        // Add the fields into the sheet
        $settings['sheet']['fields'] = $fields;
    }

    private function getFooter()
    {   // START: POCOR-6538 - Akshay patodi <akshay.patodi@mail.valuecoders.com>
        $ConfigItemTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $ConfigItem =   $ConfigItemTable
                            ->find()
                            ->select(['zonevalue' => 'ConfigItems.value'])
                            ->where([
                                $ConfigItemTable->aliasField('name') => 'Time Zone'
                                   ]);
                            //->first();
        foreach ($ConfigItem->toArray() as $value) {
            if (!empty($value['zonevalue'])) {
                $timezone =  $value['zonevalue'];
                date_default_timezone_set($timezone);
            }
            else{
                date_default_timezone_set('Europe/London');  //POCOR-6819
            }
        }
        // END: POCOR-6538 - Akshay patodi <akshay.patodi@mail.valuecoders.com>
        $footer = [__("Report Generated") . ": "  . date("Y-m-d H:i:s")];
        return $footer;
    }

    private function getValue($entity, $table, $attr)
    {
        $value = '';
        $field = $attr['field'];
        $type = $attr['type'];
        $style = [];

        if (!empty($entity)) {
            if (!in_array($type, ['string', 'integer', 'decimal', 'text'])) {
                $method = 'onExcelRender' . Inflector::camelize($type);
                if (!$this->eventMap($method)) {
                    $event = $this->dispatchEvent($table, $this->eventKey($method), $method, [$entity, $attr]);
                } else {
                    $event = $this->dispatchEvent($table, $this->eventKey($method), null, [$entity, $attr]);
                }
                // Check $event is a valid object with the method getResult //POCOR-9272
                if ($event && method_exists($event, 'getResult')) {
                    $result = $event->getResult();
                    // Explicitly check for null to allow 0 //POCOR-9272
                    if ($result !== null) {
                        $returnedResult = $event->getResult();
                        if (is_array($returnedResult)) {
                            $value = isset($returnedResult['value']) ? $returnedResult['value'] : '';
                            $style = isset($returnedResult['style']) ? $returnedResult['style'] : [];
                        } else {
                            $value = $returnedResult;
                        }
                    }
                }
            } else {
                $method = 'onExcelGet' . Inflector::camelize($field);
                $event = $this->dispatchEvent($table, $this->eventKey($method), $method, [$entity], true);
                if ($event->getResult()) {
                    $returnedResult = $event->getResult();
                    if (is_array($returnedResult)) {
                        $value = isset($returnedResult['value']) ? $returnedResult['value'] : '';
                        $style = isset($returnedResult['style']) ? $returnedResult['style'] : [];
                    } else {
                        $value = $returnedResult;
                    }
                } elseif ($entity->has((string)$field)) {//POCOR-7485 add (string) becuase of custom fields excel on Infrastructure land
                    if ($this->isForeignKey($table, (string)$field)) {
                        $associatedField = $this->getAssociatedKey($table, (string)$field);
                        if ($entity->has($associatedField)) {
                            $value = $entity->{$associatedField}->name;
                        }
                    } else {
                        $value = $entity->{(string)$field};
                    }
                }
            }
        }

        $specialCharacters = ['=', '@'];
        //POCOR-8515 commented this code because of getting error to generate report starts
        //$firstCharacter = substr($value, 0, 1);
        // if (in_array($firstCharacter, $specialCharacters)) {
        //     // append single quote to escape special characters
        //     $value = "'" . $value;
        // }//POCOR-8515 ends

        //return ['rowData' => __($value), 'style' => $style];
        return ['rowData' => $value, 'style' => $style];//POCOR-8515
    }

    private function isForeignKey($table, $field)
    {
        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->getForeignKey()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAssociatedTable($table, $field)
    {
        $relatedModel = null;

        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->getForeignKey()) {
                    $relatedModel = $assoc;
                    break;
                }
            }
        }
        return $relatedModel;
    }

    public function getAssociatedKey($table, $field)
    {
        $tableObj = $this->getAssociatedTable($table, $field);
        $key = null;
        if (is_object($tableObj)) {
            $key = Inflector::underscore(Inflector::singularize($tableObj->getAlias()));
        }
        return $key;
    }

    private function contain(Query $query, $fields, $table)
    {
        $contain = [];
        foreach ($fields as $attr) {
            $field = $attr['field'];
            if ($this->isForeignKey($table, $field)) {
                $contain[] = $this->getAssociatedTable($table, $field)->getAlias();
            }
        }
        $query->contain($contain);
    }

    private function download($path)
    {
        $filename = basename($path);

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$filename);
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($path));
        echo file_get_contents($path);
        exit(); //POCOR-6881
    }

    private function purge($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 0];

        if ($this->isCAv4()) {
            $events['ControllerAction.Model.excel'] = 'excelV4';
            $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
        }
        return $events;
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $action = $this->_table->action;

        if (in_array($action, $this->getConfig('pages'))) {
            $toolbarButtons = isset($extra['toolbarButtons']) ? $extra['toolbarButtons'] : [];
            $toolbarAttr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Export')
            ];

            $toolbarButtons['export'] = [
                'type' => 'button',
                'label' => '<i class="fa kd-export"></i>',
                'attr' => $toolbarAttr,
                'url' => ''
            ];

            $url = $this->_table->url($action);
            $url[0] = 'excel';
            $toolbarButtons['export']['url'] = $url;
            $extra['toolbarButtons'] = $toolbarButtons;
        }
    }

    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($buttons->offsetExists('view')) {
            $export = $buttons['view'];
            $export['type'] = 'button';
            $export['label'] = '<i class="fa kd-export"></i>';
            $export['attr'] = $attr;
            $export['attr']['title'] = __('Export');

            if ($isFromModel) {
                $export['url'][0] = 'excel';
            } else {
                $export['url']['action'] = 'excel';
            }

            $pages = $this->getConfig('pages');
            if (in_array($action, $pages)) {
                $toolbarButtons['export'] = $export;
            }
        } elseif ($buttons->offsetExists('back')) {
            $export = $buttons['back'];
            $export['type'] = 'button';
            $export['label'] = '<i class="fa kd-export"></i>';
            $export['attr'] = $attr;
            $export['attr']['title'] = __('Export');

            if ($isFromModel) {
                $export['url'][0] = 'excel';
            } else {
                $export['url']['action'] = 'excel';
            }

            $pages = $this->getConfig('pages');
            if ($pages != false) {
                if (in_array($action, $pages)) {
                    $toolbarButtons['export'] = $export;
                }
            }
        }
    }
}
