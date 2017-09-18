<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\AppTable;

class CommentsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('user_comments');
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
