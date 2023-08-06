<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Network\Request;
use ArrayObject;

class AssetMakesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $i = 1/0;
        $this->table('asset_makes');
        parent::initialize($config);

        $this->belongsTo('AssetTypes', ['className' => 'FieldOption.AssetTypes']);
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

    public function onUpdateFieldIdentityTypeId(Event $event, array $attr, $action, Request $request)
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
}