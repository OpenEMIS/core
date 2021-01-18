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
        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'StaffSalaries']);
        $this->addBehavior('Institution.ImportStaff');
        //POCOR-5182 end
    }
}    