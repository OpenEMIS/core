<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Session;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class InstitutionStaffAppraisalsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
        parent::initialize($config);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        // $this->behaviors()->get('ControllerAction')->config(
        //     'actions.download.show',
        //     true
        // );

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalStatuses', ['className' => 'StaffAppraisal.AppraisalStatuses']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $userId = $this->request->query('user_id');
        $staff = $this->Users->get($userId);
        $this->controller->set('contentHeader', $staff->name. ' - ' .__('Appraisals'));
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('staff_id', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('appraisal_status_id', ['visible' => false]);
        $this->setFieldOrder(['appraisal_type_id', 'title', 'to', 'from', 'appraisal_form_id']);
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $options['type'] = 'staff';
        $userId = $this->request->query('user_id');
        if (!is_null($userId)) {
            $options['user_id'] = $userId;
        }

        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
