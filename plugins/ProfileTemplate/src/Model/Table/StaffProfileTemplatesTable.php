<?php
namespace ProfileTemplate\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Date;
use Cake\I18n\Time;
use App\Model\Table\ControllerActionTable;
use Cake\Http\ServerRequest;

class StaffProfileTemplatesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
		$this->setTable('staff_profile_templates');
        parent::initialize($config);
    }

}
