<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

class GuidanceTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('guidance_types');
        parent::initialize($config);

        $this->hasMany('Counsellings', ['className' => 'Institution.Counsellings']);
        //POCOR-9771: also protect against deleting a guidance type still referenced via the
        //counselling_guidance_types join table (the multi-select path), not just the legacy
        //single counsellings.guidance_type_id column covered by the association above.
        $this->hasMany('CounsellingGuidanceTypes', ['className' => 'Student.CounsellingGuidanceTypes']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
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
