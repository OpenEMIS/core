<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Core\Configure;

use App\Model\Table\ControllerActionTable;

class SpecialNeedsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_special_needs');
        parent::initialize($config);
        $this->behaviors()->get('ControllerAction')->config('actions.search', false);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('SpecialNeedTypes', ['className' => 'FieldOption.SpecialNeedTypes']);
        $this->belongsTo('SpecialNeedDifficulties', ['className' => 'FieldOption.SpecialNeedDifficulties']);

        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStudentRisks.calculateRiskValue'] = 'institutionStudentRiskCalculateRiskValue';
        return $events;
    }

    public function beforeAction($event)
    {
        $this->fields['special_need_type_id']['type'] = 'select';
        $this->fields['special_need_difficulty_id']['type'] = 'select';
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('special_need_date')
        ;
    }

    public function validationNonMandatory(Validator $validator)
    {
        $this->validationDefault($validator);
        return $validator->allowEmpty('comment');
    }

    private function setupTabElements()
    {
        $options = [
            'userRole' => '',
        ];

        switch ($this->controller->name) {
            case 'Students':
                $options['userRole'] = 'Students';
                break;
            case 'Staff':
                $options['userRole'] = 'Staff';
                break;
        }
$session = $this->request->session();
$guardianID = $session->read('Guardian.Guardians.id');
$studentID = $session->read('Guardian.Students.id');
        if ($this->controller->name == 'Directories') {
            $type = $this->request->query('type');
            $options['type'] = $type;
            $tabElements = $this->controller->getUserTabElements($options);
    if (!empty($guardianID)) {
        $userId = $guardianID;
        $StudentGuardianID=$this->request->session()->read('Student.Guardians.primaryKey');
        $newStudentGuardianID=$StudentGuardianID['id'];
        $url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
        $guardianstabElements = [
            'Guardians' => ['text' => __('Relation')],
            'GuardianUser' => ['text' => __('Overview')]
         ];
        $action = 'StudentGuardians';
        $actionUser = 'StudentGuardianUser';
        $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $newStudentGuardianID])]);
        $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $userId, 'StudentGuardians.id' => $newStudentGuardianID])]);
        $guardianId = $userId;
        $tabElements = array_merge($guardianstabElements, $tabElements);                
    }
} elseif ($this->controller->name == 'Students') {
    $tabElements = $this->controller->getUserTabElements($options);
    if (!empty($guardianID)) {
        $userId = $guardianID;
        $StudentGuardianID=$this->request->session()->read('Student.Guardians.primaryKey');
        $newStudentGuardianID=$StudentGuardianID['id'];
        $url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
        $guardianstabElements = [
            'Guardians' => ['text' => __('Relation')],
            'GuardianUser' => ['text' => __('Overview')]
         ];
        $tabElements = $this->controller->getGuardianTabElements($options);
        $action = 'Guardians';
        $actionUser = 'GuardianUser';
        $guardianstabElements['Guardians']['url'] = array_merge($url, ['action' => $action, 'view', $this->paramsEncode(['id' => $newStudentGuardianID])]);
        $guardianstabElements['GuardianUser']['url'] = array_merge($url, ['action' => $actionUser, 'view', $this->paramsEncode(['id' => $userId, 'StudentGuardians.id' => $newStudentGuardianID])]);
        $guardianId = $userId;
        $tabElements = array_merge($guardianstabElements, $tabElements);                
    }            
        } else {
            $tabElements = $this->controller->getUserTabElements($options);
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function afterAction(Event $event, $data)
    {
        $this->setupTabElements();
        $this->setFieldOrder(['special_need_date', 'special_need_type_id', 'special_need_difficulty_id', 'comment']);
    }

    public function institutionStudentRiskCalculateRiskValue(Event $event, ArrayObject $params)
    {
        $institutionId = $params['institution_id'];
        $studentId = $params['student_id'];
        $academicPeriodId = $params['academic_period_id'];

        $quantityResult = $this->find()
            ->where([$this->aliasField('security_user_id') => $studentId])
            ->all()->toArray();

        $quantity = !empty(count($quantityResult)) ? count($quantityResult) : 0;

        return $quantity;
    }

    public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    {
        $specialNeedList = $this->find()
            ->contain(['SpecialNeedTypes', 'SpecialNeedDifficulties'])
            ->where([$this->aliasField('security_user_id') => $studentId])
            ->all();

        $referenceDetails = [];
        foreach ($specialNeedList as $key => $obj) {
            $specialNeedName = $obj->special_need_type->name;
            $specialNeedDifficulties = $obj->special_need_difficulty->name;

            $referenceDetails[$obj->id] = __($specialNeedName) . ' (' . __($specialNeedDifficulties) . ')';
        }

        // tooltip only receieved string to be display
        $reference = '';
        if (!empty($referenceDetails)) {
            foreach ($referenceDetails as $key => $referenceDetailsObj) {
                $reference = $reference . $referenceDetailsObj . ' <br/>';
            }
        } else {
            $reference = __('No Special Need');
        }

        return $reference;
    }
}
