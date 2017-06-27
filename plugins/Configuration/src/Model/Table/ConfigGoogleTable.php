<?php
namespace Configuration\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

class ConfigGoogleTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('idp_google');
        parent::initialize($config);
        $this->addBehavior('Configuration.Authentication');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {

    }
}
