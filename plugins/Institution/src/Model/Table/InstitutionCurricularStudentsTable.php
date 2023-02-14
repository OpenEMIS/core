<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;


class InstitutionCurricularStudentsTable extends ControllerActionTable
{	
	use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars']);

      //  $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
    }

    /*public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->request->query;
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $institutionId = $extra['institution_id'];
        $selectedAcademicPeriodId = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
       
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;
        $extra['elements']['control'] = [
            'name' => 'Institution.Associations/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];

    }*/
	
}
