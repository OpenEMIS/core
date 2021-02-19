<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\I18n\Date;

class StudentWithdrawalReport extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('student_withdraw_reasons');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
   
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions',         'foreignKey' => 'institution_id']);
       
       $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);

        $this->addBehavior('Report.ReportList');
        // $this->addBehavior('Report.ReportList');
        // $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions('Institutions');
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        echo "<pre>";
        print_r($settings); die();
        $requestData = json_decode($settings['process']['params']);
        $institution_id = $requestData->institution_id;
        $where = [];
        if ($institution_id != 0) {
            $where['Institutions.id'] = $institution_id;
        }
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        echo "<pre>";
        print_r($settings); die('rahul');
       
    }
}
