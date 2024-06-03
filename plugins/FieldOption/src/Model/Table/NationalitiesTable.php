<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use ArrayObject;

class NationalitiesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('nationalities');
        parent::initialize($config);

        $this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
        $this->hasMany('UserNationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'nationality_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->field('identity_type_id', [
            'type' => 'select',
            'after' => 'name',
            'entity' => $extra['entity']
        ]);

    }

    public function onUpdateFieldIdentityTypeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $Nationalities = $this;
        $identityTypes = $this->IdentityTypes
            ->find('list')
            ->toArray();

        $attr['options'] = $identityTypes;

        return $attr;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    //POCOR-7980
    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
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
            case 'is_refugee':  
                return __('Refugee');
            case 'identity_type_id':  
                return __('Identity Type');
            case 'external_validation':
                return __('External Validation');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    //POCOR-7980

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //update information on security user table
        $listeners = [
            TableRegistry::get('User.Users')
        ];
        $this->dispatchEventToModels('Model.Nationalities.onChange', [$entity], $this, $listeners);
    }
    }
