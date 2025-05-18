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

class AssetMakesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('asset_makes');
        parent::initialize($config);

        $this->belongsTo('AssetTypes', ['className' => 'Institution.AssetTypes']);
//        $this->hasMany('UserNationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'nationality_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
    }

    public function afterAction(Event $event, ArrayObject $extra) 
    {
        $this->field('asset_type_id', [
            'type' => 'select', 
            'after' => 'name',
            'entity' => $extra['entity']
        ]);

    }

    public function onUpdateFieldAssetTypeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        $identityTypes = $this->AssetTypes
            ->find('list')
            ->toArray();

        $attr['options'] = $identityTypes;

        return $attr;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //update information on security user table
//        $listeners = [
//            TableRegistry::get('User.Users')
//        ];
//        $this->dispatchEventToModels('Model.Nationalities.onChange', [$entity], $this, $listeners);
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
            case 'asset_type_id': 
                return __('Asset Type');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}