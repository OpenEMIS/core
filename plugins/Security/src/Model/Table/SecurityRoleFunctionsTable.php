<?php
namespace Security\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class SecurityRoleFunctionsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);
        $this->belongsTo('SecurityFunctions', ['className' => 'Security.SecurityFunctions']);
    }
	
    public function findAllSecurityRoleFunctions(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $userId = $options['user']['id'];
        $superAdmin = $options['user']['super_admin'];
		
        // if he is not super admin
        if($superAdmin == 0){  
            $userAccessRoles = TableRegistry::get('Institution.Institutions')->getInstitutionRoles($userId, $institutionId);
            $query->contain(['SecurityRoles', 'SecurityFunctions'])
                ->where([
                    $this->aliasField('security_role_id IN')=>$userAccessRoles
                ]);
        }
        
    }
}
