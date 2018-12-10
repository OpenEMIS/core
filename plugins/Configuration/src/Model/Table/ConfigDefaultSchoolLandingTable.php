<?php
namespace Configuration\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

use App\Model\Table\ControllerActionTable;

class ConfigDefaultSchoolLandingTable extends ControllerActionTable
{
    use OptionsTrait;

    CONST STAFF_RELEASE_BY_TYPES = 'staff_release_by_types';
    CONST STAFF_RELEASE_BY_SECTORS = 'staff_release_by_sectors';
    CONST RESTRICT_STAFF_RELEASE_BETWEEN_SAME_TYPE = 'restrict_staff_release_between_same_type';
    CONST SELECTION_DISABLE = "0";

    public function initialize(array $config)
    {
        $this->table('config_items');
        parent::initialize($config);

        $this->addBehavior('Configuration.ConfigItems');

        $this->toggle('add', false);
        $this->toggle('remove', false);
    }
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name', ['visible' => ['index' => true]]);
        $this->field('code', ['type' => 'hidden']);
        $this->field('type', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('label', ['visible' => ['view' => true, 'edit' => true], 'type' => 'readonly']);
        $this->field('default_value', ['visible' => ['view' => true]]);
        $this->field('editable', ['visible' => false]);
        $this->field('visible', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('option_type', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $extra['elements']['controls'] = $this->buildSystemConfigFilters();
        $this->checkController();
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('type') => 'Institution']);
    }

    // public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    // {
    //     // $this->setupFields($entity);
    // }

    public function onGetDefaultValue(Event $event, Entity $entity)
    {
        pr($entity); die;
        $value = '';
        if ($entity->has('code')) {

        }
    }
}
