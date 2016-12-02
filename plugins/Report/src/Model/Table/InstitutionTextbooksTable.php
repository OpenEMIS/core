<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionTextbooksTable extends AppTable  {
    public function initialize(array $config) {
        parent::initialize($config);
        
        $this->belongsTo('Textbooks', ['className' => 'Textbook.Textbooks', 'foreignKey' => ['textbook_id', 'academic_period_id']]);
        $this->belongsTo('TextbookStatuses', ['className' => 'Textbook.TextbookStatuses']);
        $this->belongsTo('TextbookConditions', ['className' => 'Textbook.TextbookConditions']);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        
        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
        // $this->addBehavior('Report.CustomFieldList', [
        //     'model' => 'Textbook.Textbooks',
        //     'formFilterClass' => null,
        //     'fieldValueClass' => ['className' => 'StaffCustomField.StaffCustomFieldValues', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true],
        //     'tableCellClass' => ['className' => 'StaffCustomField.StaffCustomTableCells', 'foreignKey' => 'staff_id', 'dependent' => true, 'cascadeCallbacks' => true]
        // ]);
    }

    public function beforeAction(Event $event) 
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }
    
    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        // $query->where([$this->aliasField('is_staff') => 1]);
    }

    // public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    // {
    //     $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
    //     $identity = $IdentityType->getDefaultEntity();
        
    //     foreach ($fields as $key => $field) { 
    //         //get the value from the table, but change the label to become default identity type.
    //         if ($field['field'] == 'identity_number') { 
    //             $fields[$key] = [
    //                 'key' => 'Staff.identity_number',
    //                 'field' => 'identity_number',
    //                 'type' => 'string',
    //                 'label' => __($identity->name)
    //             ];
    //             break;
    //         }
    //     }
    // }
}
