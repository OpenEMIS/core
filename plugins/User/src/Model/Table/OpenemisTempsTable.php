<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\UserTrait;
use Cake\I18n\Time;
use Cake\Network\Session;
use Cake\Datasource\ConnectionManager;

class OpenemisTempsTable extends AppTable
{
    
    public function initialize(array $config)
    {
        $this->table('openemis_temps');
        parent::initialize($config);     

        $this->displayField('openemis_no');
    }

}
