<?php
namespace Security\Model\Table;

use Cake\ORM\Query;
use App\Model\Table\AppTable;

class SecurityFunctionsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'through' => 'Security.SecurityRoleFunctions'
        ]);
    }

    public function findPermissions(Query $query, $options)
    {
        $roleId = $options['roleId'];
        $module = $options['module'];

        $query
        ->find('visible')
        ->select([
            $this->aliasField('id'),
            $this->aliasField('name'),
            $this->aliasField('controller'),
            $this->aliasField('module'),
            $this->aliasField('category'),
            $this->aliasField('_view'),
            $this->aliasField('_add'),
            $this->aliasField('_edit'),
            $this->aliasField('_delete'),
            $this->aliasField('_execute'),
            $this->aliasField('description'),
            'Permissions.id',
            'Permissions._view',
            'Permissions._add',
            'Permissions._edit',
            'Permissions._delete',
            'Permissions._execute'
        ])
        ->leftJoin(
            ['Permissions' => 'security_role_functions'],
            [
                'Permissions.security_function_id = '. $this->aliasField('id'),
                'Permissions.security_role_id' => $roleId
            ]
        )
        ->where([$this->aliasField('module') => $module])
        ->order([
            $this->aliasField('order')
        ])
        ;
        return $query;
    }
}
