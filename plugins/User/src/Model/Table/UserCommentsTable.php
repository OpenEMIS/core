<?php
namespace User\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class UserCommentsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('CommentTypes', ['className' => 'User.CommentTypes', 'foreignKey' => 'comment_type_id']);
    }

    public function findIndex(Query $query, array $options)
    {
        $querystring = $options['querystring'];
        if (array_key_exists('security_user_id', $querystring) && !empty($querystring['security_user_id'])) {
            $query->where([$this->aliasField('security_user_id') => $querystring['security_user_id']]);
        }
        return $query;
    }
}