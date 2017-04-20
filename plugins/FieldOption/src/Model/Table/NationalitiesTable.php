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

class NationalitiesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('nationalities');
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

    public function onUpdateFieldIdentityTypeId(Event $event, array $attr, $action, Request $request)
    {
        $usedIdentityType = $this
                            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                            ->select([
                                'id' => $this->aliasfield('identity_type_id')
                            ])
                            ->where([
                                $this->aliasfield('identity_type_id IS NOT') => null
                            ])
                            ->toArray();

        if ($action == 'edit') {
            $entity = $attr['entity'];
            if ($entity->has('identity_type_id') && !empty($entity->identity_type_id) && !empty($usedIdentityType)) {
                unset($usedIdentityType[$entity->identity_type_id]);
            }
        }
        
        $options = $this->IdentityTypes->find('list')
                    ->where([
                        $this->IdentityTypes->aliasField('id NOT IN') => $usedIdentityType
                    ])
                    ->toArray();
        $attr['options'] = $options;

        return $attr;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!empty($entity->identity_type_id)) {
            //update information on security user table
            $listeners = [
                TableRegistry::get('User.Users')
            ];
            $this->dispatchEventToModels('Model.Nationalities.onChange', [$entity], $this, $listeners);
        }
    }
}