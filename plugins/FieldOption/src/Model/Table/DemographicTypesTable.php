<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use ArrayObject;
use Cake\ORM\Entity;

class DemographicTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('demographic_types');
        parent::initialize($config);

        $this->hasMany('UserDemographics', ['className' => 'Student.StudentDemographics']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');

        $this->fields['name']['required'] = true;

        $this->toggle('remove', false);
        $this->toggle('add', false);
    }

    public function afterAction(EventInterface $event, ArrayObject $extra) 
    {
        $this->field('description', [
            'after' => 'name',
        ]);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
