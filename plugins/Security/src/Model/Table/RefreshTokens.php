<?php
namespace Security\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Log\Log;

class RefreshTokensTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
	}

	public function findRefreshToken(Query $query, array $options)
	{
		$userId = $options['user_id'];
		$module = $options['module'];
		$moduleType = isset($options['module_type']) ? $options['module_type'] : null;

        return $query
        	->select([$this->aliasField('security_user_id')])
        	->where([
                $this->aliasField('security_user_id') => $userId,
                $this->aliasField('module') => $module,
                $this->aliasField('module_type') => $moduleType
            ]);
	}
}
