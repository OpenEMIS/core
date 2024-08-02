<?php
namespace Guardian\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;

class StudentsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('student_guardians');
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
            $session = $this->request->getSession();
            $session->write('Student.Guardians.primaryKey', $this->paramsDecode($this->request->getParam('pass')['1']));
            $options = ['entity' => $entity, 'id' => $entity->student_id, 'userRole' => 'Students'];
            $tabElements = $this->controller->getUserTabElements($options);
        }

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
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
        if ($this->controller->getName() == 'Directories') {
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
        // $session = $this->request->getSession();
        // $userId = $session->read('Directory.Directories.id');
        $userId = $this->getUserId();
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);
        $toolbarButtons = $extra['toolbarButtons'];
        if($toolbarButtons->offsetExists('back')) {
            $url = $toolbarButtons['back']['url'];
            if(isset($this->request->getParam('pass')[1]) ) {
                $decodeQueryString = $this->request->getParam('pass')[1];
                $queryString = $this->paramsDecode($decodeQueryString);
                $url['1'] = $this->paramsEncode($queryString);
                $url = $this->setQueryString($url,['id'=>$queryString['security_user_id'], 'security_user_id'=>$queryString['security_user_id']]);
            }
            $toolbarButtons['back']['url'] = $url;
        }
        $extra['toolbarButtons'] = $toolbarButtons;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $newButtons = [];
        if (isset($buttons['view'])) {
            $security_user_id = $this->getUserId();
            $pass = $this->paramsDecode($buttons['view']['url'][1]);
            $pass['security_user_id'] = $this->getUserId();
            $pass['student_id'] = $entity->student_id;
            $pass['userRole'] = 'Students';
            $buttons['view']['url'][1] = $this->paramsEncode($pass);
            $newButtons['view'] = $buttons['view'];
        }

        return $newButtons;
    }


    public function getUserId()
    {
        $userId = '';
        $queryString = $this->getQueryString();
        $session = $this->request->getSession();
        $userId = (isset($queryString['security_user_id']) && !empty($queryString['security_user_id'])) ? $queryString['security_user_id'] : $session->read('Directory.Directories.id');
        return $userId;
    }
}
