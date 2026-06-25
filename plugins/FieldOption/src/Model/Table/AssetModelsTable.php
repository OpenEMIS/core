<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use ArrayObject;

class AssetModelsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {

        $this->setTable('asset_models');
        parent::initialize($config);

        $this->belongsTo('AssetMakes', ['className' => 'FieldOption.AssetMakes']);
//        $this->hasMany('UserNationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'nationality_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add'],
            'Staff' => ['index', 'add']
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->setProvider('custom', $this);
        $validator = parent::validationDefault($validator);
        return $validator
            ->requirePresence('asset_make_id');
    }

    public function afterAction(EventInterface $event, ArrayObject $extra) 
    {
        $this->field('asset_make_id', [
            'type' => 'select', 
            'after' => 'name',
            'entity' => $extra['entity']
        ]);

    }

    public function onUpdateFieldAssetMakeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $assetMakes = $this->AssetMakes
            ->find('list')
            ->toArray();

        $attr['options'] = $assetMakes;

        return $attr;
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //update information on security user table
//        $listeners = [
//            TableRegistry::getTableLocator()->get('User.Users')
//        ];
//        $this->dispatchEventToModels('Model.Nationalities.onChange', [$entity], $this, $listeners);
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
            case 'asset_make_id':  
                return __('Asset Makes');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}