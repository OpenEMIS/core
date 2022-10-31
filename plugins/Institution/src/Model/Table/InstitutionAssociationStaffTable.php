<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionAssociationStaffTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InstitutionAssociations', ['className' => 'Institution.InstitutionAssociations', 'foreignKey' => 'institution_association_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->addBehavior('CompositeKey');
    }

     public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        // $options = ['type' => 'staff'];
        // $tabElements = $this->controller->getCareerTabElements($options);
        // $this->controller->set('tabElements', $tabElements);
        // $this->controller->set('selectedAction', 'Associations');
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->getUserId();
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'StaffAssociations');
    }

     public function getUserId()
    {
        $userId = null;
        if (!is_null($this->request->query('user_id'))) {
            $userId = $this->request->query('user_id');
        } else {
            $session = $this->request->session();
            if ($session->check('Staff.Staff.id')) {
                $userId = $session->read('Staff.Staff.id');
            }
        }

        return $userId;
    }
}
