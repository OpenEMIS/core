<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class AwardsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_awards');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    private function setupTabElements()
    {
        switch ($this->controller->name) {
            case 'Students':
                $tabElements = $this->controller->getAcademicTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
            case 'Staff':
                $tabElements = $this->controller->getProfessionalTabElements();
                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
            case 'Directories':
            case 'Profiles':
                $type = $this->request->query('type');
                $options['type'] = $type;
                if ($this->action == 'index') {
                    $tabElements = $this->controller->getAcademicTabElements($options);
                } elseif ($type == 'student') {
                    $tabElements = $this->controller->getAcademicTabElements($options);
                } else {
                    $tabElements = $this->controller->getProfessionalTabElements($options);
                }

                $this->controller->set('tabElements', $tabElements);
                $this->controller->set('selectedAction', $this->alias());
                break;
        }
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }
}
