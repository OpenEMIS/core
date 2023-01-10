<?php
namespace Guardian\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;

class StudentsTable extends ControllerActionTable
{
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

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    private function setupTabElements($entity = null)
    {

        if ($this->action != 'view') {
            $tabElements = $this->controller->getGuardianStudentTabElements();
        } elseif ($this->action == 'view') {
            $session = $this->request->session();
            $session->write('Student.Guardians.primaryKey', $this->paramsDecode($this->request->params['pass']['1']));
            $tabElements = $this->controller->getUserTabElements(['entity' => $entity, 'id' => $entity->student_id, 'userRole' => 'Students']);
        }

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function afterAction(Event $event, $data)
    {
        if ($this->action != 'view') {
            $this->setupTabElements();
        }

        $this->setFieldOrder([
            'photo_content', 'openemis_no', 'student_id',
        ]);
    }

    public function onGetGuardianId(Event $event, Entity $entity)
    {
        if ($entity->has('_matchingData')) {
            return $entity->_matchingData['Users']->name;
        }
    }

    public function beforeAction(Event $event)
    {
        if ($this->controller->name == 'Directories') {
            $studentId = $this->Session->read('Directory.Directories.id');
        } else {
            $studentId = $this->Session->read('Student.Students.id');
        }
        $this->field('student_id',  ['type' => 'select']);
        $this->field('guardian_id',  ['type' => 'hidden']);
        $this->field('guardian_relation_id', ['type' => 'hidden']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $userId = $session->read('Directory.Directories.id');
        $conditions[$this->aliasField('guardian_id')] = $userId;
        $query->where($conditions, [], true);

        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
    }

    public function viewBeforeAction(Event $event)
    {
        $this->field('photo_content', ['type' => 'image', 'order' => 0]);
        $this->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupTabElements($entity);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $newButtons = [];
        if (array_key_exists('view', $buttons)) {
            $newButtons['view'] = $buttons['view'];
        }

        return $newButtons;
    }
}
