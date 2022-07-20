<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
/**
 * initalize Class Report Cards class
 * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
 * 
 */
class ClassReportCardsTable extends ControllerActionTable
{
     // for status
     CONST NEW_REPORT = 1;
     CONST IN_PROGRESS = 2;
     CONST GENERATED = 3;
     CONST PUBLISHED = 4;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ClassTemplates', ['className' => 'ProfileTemplate.ClassTemplates', 'foreignKey' => 'class_profile_template_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->addBehavior('CompositeKey');
    }
}
