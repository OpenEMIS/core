<?php
namespace SpecialNeeds\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Class for classification in special need > Services > classification dropdown
 * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
 * @ticket POCOR-6873
 */

class SpecialNeedsServiceClassificationTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        $this->setTable('special_needs_service_classification');
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
    }
    public function beforeAction() {
        $this->field('name', ['length' => 75]);
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
