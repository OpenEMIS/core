<?php
namespace GuardianNav\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Network\Session;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use Cake\Database\Exception as DatabaseException;

class StudentGuardiansTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('StudentUser', ['className' => 'Student.GuardianUser', 'foreignKey' => 'guardian_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('GuardianRelations', ['className' => 'Student.GuardianRelations', 'foreignKey' => 'guardian_relation_id']);

        // to handle field type (autocomplete)
        $this->addBehavior('OpenEmis.Autocomplete');
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
        $this->addBehavior('ControllerAction.Image');
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $guardianId = $this->Session->read('Auth.User.id');
        $conditions[$this->aliasField('guardian_id')] = $guardianId;
        $search = $this->getSearchKey();
        $query->where($conditions, [], true);
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }

        if (!isset($this->request->query['sort'])) {
            $orders = [
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('last_name')
            ];

            $query->order($orders);
        }
    }
}    