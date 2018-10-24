<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class DemographicTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_demographics');
        parent::initialize($config);

        $this->belongsTo('DemographicTypes', ['className' => 'Student.DemographicTypes', 'foreignKey' => 'demographic_types_id']);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('User.SetupTab');

        $this->toggle('remove', false);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $userId = $this->paramsDecode($requestQuery['queryString'])['security_user_id'];
        $query = $this
            ->find()
            ->where([$this->aliasField('security_user_id') => $userId])
            ->first();

        if (!empty($query)) {
            $this->toggle('add', false);
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $demographicTypes = TableRegistry::get('FieldOption.DemographicTypes');
        $demographicTypesArray = $demographicTypes
            ->find()
            ->toArray();

        $demographicTypes->fields['demographicsTypes'] = $demographicTypesArray;
        $demographicTypes->fields['entity'] = $entity;
        $this->field('demographic_types_id', [
            'type' => 'element',
            'element' => 'User.Demographics/Demographic_description',
            'fields' => $demographicTypes->fields,
            'formFields' => [],
            'model' => 'Demographics',
            'className' => 'User.Demographics'
        ]);
    }

    public function beforeAction($event) {
        $gradeOptions = $this->getIndigenousOptions();
        $this->fields['indigenous']['type'] = 'select';
        $this->fields['indigenous']['options'] = $gradeOptions;
    }

    public function getIndigenousOptions() {
        $IndigenousOptions = array();
        $IndigenousOptions[0] = 'Yes';
        $IndigenousOptions[1] = 'No';
        $IndigenousOptions[2] = 'Unknown';

        return $IndigenousOptions;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'demographic_types_id') {
            return __('Wealth Quintile');
        } elseif ($field == 'indigenous') {
            return __('Indigenous People');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
