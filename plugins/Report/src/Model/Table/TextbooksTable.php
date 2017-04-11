<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TextbooksTable extends AppTable  {
    public function initialize(array $config) {
        parent::initialize($config);
        
        $this->belongsTo('Textbooks',           ['className' => 'Textbook.Textbooks', 'foreignKey' => ['textbook_id', 'academic_period_id']]);
        $this->belongsTo('AcademicPeriods',     ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades',     ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects',   ['className' => 'Education.EducationSubjects']);
        
        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event) 
    {
        $this->fields = [];
        $this->ControllerAction->field('academic_period_id', ['select' => false]);
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }
    
    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request) 
    {
        $attr['options'] = $this->AcademicPeriods->getYearList();
        $attr['default'] = $this->AcademicPeriods->getCurrent();
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;

        if ($academicPeriodId!=0) {
            $query->where([
                $this->aliasField('academic_period_id') => $academicPeriodId
            ]);
        }
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
