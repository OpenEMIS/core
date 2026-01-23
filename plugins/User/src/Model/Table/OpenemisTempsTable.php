<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\UserTrait;
use Cake\I18n\Time;
use Cake\Http\Session;
use Cake\Datasource\ConnectionManager;

class OpenemisTempsTable extends AppTable
{
    
    public function initialize(array $config): void
    {
        $this->setTable('security_users_openemis_no');
        parent::initialize($config);     

        $this->getDisplayField('openemis_no');
    }

}
