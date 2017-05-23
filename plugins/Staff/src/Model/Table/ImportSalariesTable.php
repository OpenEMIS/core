<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportSalariesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Staff',
            'model' => 'Salaries'
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) 
    {
        $plugin = $toolbarButtons['back']['url']['plugin'];
        $controller = $toolbarButtons['back']['url']['controller'];
        if ($plugin == 'Directory') {
            $toolbarButtons['back']['url']['action'] = 'StaffSalaries';
        } else if ($plugin == 'Staff') {
            $toolbarButtons['back']['url']['action'] = 'Salaries';
        }
    }

    public function beforeAction($event) 
    {
        $session = $this->request->session();
        if ($session->check('Staff.Staff.id')) {
            $this->staffId = $session->read('Staff.Staff.id');
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        if (empty($this->staffId)) {
            $rowInvalidCodeCols['staff_id'] = __('No active staff');
            $tempRow['staff_id'] = false;
            return false;
        } else {
            //necessary data to be sent for saving.
            $tempRow['staff_id'] = $this->staffId;
            $tempRow['net_salary'] = $tempRow['gross_salary'];
            return true;
        }
    }
}
