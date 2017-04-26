<?php
namespace ReportCard\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

class ReportCardSubjectsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

        $this->addBehavior('CompositeKey');
    }
}
