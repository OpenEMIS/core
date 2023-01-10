<?php
namespace Profile\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Text;
use App\Model\Table\ControllerActionTable;
use Cake\Core\Configure;

class StudentsTable extends ControllerActionTable
{
    private $editButtonAction = 'GuardianUser';

    public function initialize(array $config)
    {
        $this->table('student_guardians');
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

    public function afterAction(Event $event, $data)
    {
        $this->setFieldOrder([
            'photo_content', 'openemis_no', 'student_id'
        ]);
    }

    public function beforeAction(Event $event)
    {
        $this->field('student_id', ['type' => 'select']);
        $this->field('guardian_id', ['type' => 'hidden']);
        $this->field('guardian_relation_id', ['type' => 'hidden']);
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

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = $entity->user->openemis_no;

        return $value;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $urlButtons = [
            'plugin' => $this->controller->plugin,
            'controller' => $this->controller->name,
            'action' => 'ProfileStudentUser'
        ];

        if (array_key_exists('view', $buttons) && $entity->has('_matchingData')) {
            $buttons['view']['url'] = $urlButtons;
            $buttons['view']['url'][0] = 'view';
            $buttons['view']['url'][1] = $this->paramsEncode(['id' =>  $entity->_matchingData['Users']->id, 'ProfileGuardians.id' => $entity->id]);
        }
        return $buttons;
    }
}
