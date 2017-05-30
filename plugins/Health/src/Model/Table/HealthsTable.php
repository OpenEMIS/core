<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class HealthsTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('user_healths');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
    }

    public function onGetBloodType(Event $event, Entity $entity)
    {
        $bloodTypeOptions = $this->getSelectOptions('Health.blood_types');
        return $bloodTypeOptions[$entity->blood_type];
    }

    public function onGetHealthInsurance(Event $event, Entity $entity)
    {
        $healthInsuranceOptions = $this->getSelectOptions('general.yesno');
        return $healthInsuranceOptions[$entity->health_insurance];
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        // always redirect to view page if got record
        if ($data->count() == 1) {
            $entity = $data->first();
            $action = $this->url('view');
            $action[1] = $this->paramsEncode(['id' => $entity->id]);
            $event->stopPropagation();
            return $this->controller->redirect($action);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);

        // Remove back toolbarButton from directory>health>overview (POCOR-3358)
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        unset($toolbarButtonsArray['back']);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end POCOR-3358
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldBloodType(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('Health.blood_types');
        return $attr;
    }

    public function onUpdateFieldHealthInsurance(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('blood_type');
        $this->field('health_insurance', ['after' => 'medical_facility']);
    }
}
