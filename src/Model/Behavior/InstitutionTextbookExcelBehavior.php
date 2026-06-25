<?php
namespace App\Model\Behavior;

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
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

// Events
// public function onExcelBeforeGenerate(EventInterface $event, ArrayObject $settings) {}
// public function onExcelGenerate(EventInterface $event, $writer, ArrayObject $settings) {}
// public function onExcelGenerateComplete(EventInterface $event, ArrayObject $settings) {}
// public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query) {}
// public function onExcelStartSheet(EventInterface $event, ArrayObject $settings, $totalCount) {}
// public function onExcelEndSheet(EventInterface $event, ArrayObject $settings, $totalProcessed) {}
// public function onExcelGetLabel(EventInterface $event, $column) {}

class InstitutionTextbookExcelBehavior extends Behavior
{
    use EventTrait;

    private $events = [];

    protected $_defaultConfig = [
        'folder' => 'export',
        'default_excludes' => ['modified_user_id', 'modified', 'created', 'created_user_id', 'password'],
        'excludes' => [],
        'limit' => 100000,
        'pages' => [],
        'autoFields' => true,
        'orientation' => 'landscape', // or portrait
        'sheet_limit' =>  1000000, // 1 mil rows and header row
        'auto_contain' => true
    ];

    public function initialize(array $config): void
    {
        $this->getConfig('excludes', array_merge($this->getConfig('default_excludes'), $this->getConfig('excludes')));
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

        $generate($_settings);

        $labelArray = array("Academic Period","Textbook ID","Textbook","Condition","Status","Allocated To","Student Status");

        foreach($labelArray as $label) {
            $headerRow[] = $this->getFields($this->_table, $settings, $label);
        }

        $data = $this->getData($settings);
        $writer->writeSheetRow('InstitutionsTextbooks', $headerRow);

        foreach($data as $row) {
            if(array_filter($row)) {
                $writer->writeSheetRow('InstitutionsTextbooks', $row);
            }
        }

        $blankRow[] = [];
        $footer = $this->getFooter();
        $writer->writeSheetRow('InstitutionsTextbooks', $blankRow);
        $writer->writeSheetRow('InstitutionsTextbooks', $footer);


        $filepath = $_settings['path'] . $_settings['file'];
        $_settings['file_path'] = $filepath;
        $writer->writeToFile($_settings['file_path']);
        $this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerateComplete'), 'onExcelGenerateComplete', [$_settings]);

        if ($_settings['download']) {
            $this->download($filepath);
        }

        if ($_settings['purge']) {
            $this->purge($filepath);
        }
        return $_settings;
    }



    private function getData($settings) {

        $session = $this->_table->request->getSession();
        $institution_id = $session->read('Institution.Institutions.id') ? $session->read('Institution.Institutions.id'): 0;

        if(empty($institution_id) && isset($this->_table->request->getParam('pass')[1])) {
            $institution_id = $this->_table->paramsDecode($this->_table->request->getParam('pass')[1])['institution_id'];
        }

        $subject_id = !empty($this->_table->request->getQuery('subject')) ? $this->_table->request->getQuery('subject') : 0;
        $period_id = !empty($this->_table->request->getQuery('period')) ? $this->_table->request->getQuery('period') : 0;
        $grade_id = !empty($this->_table->request->getQuery('grade')) ? $this->_table->request->getQuery('grade') : 0;

        $InstitutionTextbooks = TableRegistry::getTableLocator()->get('Institution.InstitutionTextbooks');


        $where1 = [];
        $where2 = [];
        $where3 = [];
        if ( $subject_id > 0) {
            $where1 = [$InstitutionTextbooks->aliasField('education_subject_id') => $subject_id];
        }

        if ( $period_id > 0) {
            $where2 = [$InstitutionTextbooks->aliasField('academic_period_id') => $period_id];
        }

        // if ( $grade_id > 0) {
        //     $where3 = [$InstitutionTextbooks->aliasField('education_grade_id') => $grade_id];
        // }

        //array("Academic Period","Textbook ID","Textbook","Condition","Status","Allocated To","Student Status");

        $record = $InstitutionTextbooks->find()
        ->contain([
            'Institutions',
            'EducationGrades',
            'AcademicPeriods',
            'Textbooks',
            'TextbookStatuses',
            'TextbookConditions',
            'Users'
        ])
        ->where([
            $InstitutionTextbooks->aliasField('institution_id') => $institution_id,
            $where1,
            $where2,
            $where3,
        ])

        ->order([
            $InstitutionTextbooks->aliasField('id'),
        ])
        ->toArray();
        $result = [];

        if (!empty($record)) {
            $connection = ConnectionManager::get('default');
            foreach ($record as $key => $value) {

                $student_query = $connection->execute('SELECT `name` FROM student_statuses WHERE id="'.$value['user']['status'].'"');
                $student_data = $student_query->fetch('assoc');
                $student_status = '';
                if(!empty($student_data)){
                    $student_status = $student_data['name'];
                }

                $textbook_query = $connection->execute('SELECT `title` FROM textbooks WHERE id="'.$value['textbook_id'].'"');
                $textbook_data = $textbook_query->fetch('assoc');
                $textbook_name = '';
                if(!empty($textbook_data)){
                    $textbook_name = $textbook_data['title'];
                }


                $result[$key] = [$value['academic_period']['name'], $value['code'], "$textbook_name",$value['textbook_condition']['name'],$value['textbook_status']['name'],$value['user']['first_name'].' '.$value['user']['last_name'],"$student_status"];
            }
        }

        // echo '<pre>';
        // // print_r($institution_id);
        // // print_r($where1);
        // // print_r($where2);
        // // print_r($where3);
        // // print_r($record);
        // print_r($result);
        // die;

        return $result;
    }

    private function getFields($table, $settings, $label)
    {
        $language = I18n::getLocale();
        $module = $this->_table->getAlias();

        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $label, $language], true);
        return $event->getResult();
    }

    private function getFooter()
    {
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
                if ($event->getResult()) {
                    $returnedResult = $event->getResult();
                    if (is_array($returnedResult)) {
                        $value = isset($returnedResult['value']) ? $returnedResult['value'] : '';
                        $style = isset($returnedResult['style']) ? $returnedResult['style'] : [];
                    } else {
                        $value = $returnedResult;
                    }
                }
            } else {
                $method = 'onExcelGet' . Inflector::camelize($field);
                $event = $this->dispatchEvent($table, $this->eventKey($method), $method, [$entity], true);
                if ($event->result) {
                    $returnedResult = $event->result;
                    if (is_array($returnedResult)) {
                        $value = isset($returnedResult['value']) ? $returnedResult['value'] : '';
                        $style = isset($returnedResult['style']) ? $returnedResult['style'] : [];
                    } else {
                        $value = $returnedResult;
                    }
                } elseif ($entity->has($field)) {
                    if ($this->isForeignKey($table, $field)) {
                        $associatedField = $this->getAssociatedKey($table, $field);
                        if ($entity->has($associatedField)) {
                            $value = $entity->{$associatedField}->name;
                        }
                    } else {
                        $value = $entity->{$field};
                    }
                }
            }
        }

        $specialCharacters = ['=', '@'];
        $firstCharacter = substr($value, 0, 1);
        if (in_array($firstCharacter, $specialCharacters)) {
            // append single quote to escape special characters
            $value = "'" . $value;
        }

        return ['rowData' => __($value), 'style' => $style];
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

    public function generate($settings = [])
    {
        $language = I18n::getLocale();
        $module = $this->_table->getAlias();
        //echo '<pre>';print_r($module);

        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, 'postal_code', $language], true);
        return $event;
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
        echo file_get_contents($path); die; // POCOR-3627
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
