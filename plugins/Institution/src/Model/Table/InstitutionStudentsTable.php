<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Chronos\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Core\Configure;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class InstitutionStudentsTable extends ControllerActionTable
{
	public function initialize(array $config)
    { 
        parent::initialize($config);

        // Associations
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
    }

}