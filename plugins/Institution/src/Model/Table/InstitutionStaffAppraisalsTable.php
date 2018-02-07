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
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class InstitutionStaffAppraisalsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff_appraisals');
        parent::initialize($config);

        // for file upload
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalStatuses', ['className' => 'StaffAppraisal.AppraisalStatuses']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $userId = $this->request->query('user_id');
        $staff = $this->Users->get($userId);
        $this->staff = $staff;
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

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('staff_id', ['type' => 'hidden', 'value' => $this->staff->id]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')]]);
        $this->field('appraisal_type_id', ['attr' => ['label' => __('Type')], 'type' => 'select']);
        $this->field('appraisal_form_id', ['type' => 'select', 'options' => []]);
        $this->field('appraisal_status_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldAppraisalTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['onChangeReload'] = true;
            if ($request->data($this->aliasField('appraisal_type_id'))) {
                $appraisalTypeId = $request->data($this->aliasField('appraisal_type_id'));

            }
            return $attr;
        }
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
