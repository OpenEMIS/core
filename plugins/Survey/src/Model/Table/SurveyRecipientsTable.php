<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\I18n\Time;


//POCOR-7271
class SurveyRecipientsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('survey_filters');
        parent::initialize($config);
        $this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
        $this->toggle('add', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('custom_module_id', ['type' => 'select']);
        $this->setFieldOrder(['name', 'custom_module_id']);
    }

}
