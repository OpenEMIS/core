<?php
namespace Examination\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use ArrayObject;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Utility\Security;

class ExaminationCentresInstitutionsTable extends ControllerActionTable {

    private $examCentreId = null;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
    	if ($entity->isNew()) {
    		$hashString = $entity->examination_centre_id . ',' . $entity->institution_id;
            $entity->id = Security::hash($hashString, 'sha256');
    	}
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->examCentreId = $this->ControllerAction->getQueryString('examination_centre_id');
        $this->fields['institution_id']['type'] = 'integer';
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if (is_null($this->examCentreId)) {
            $event->stopPropagation();
            $this->controller->redirect(['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres', 'index']);
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
    	$examCentre = $this->ExaminationCentres->get($this->examCentreId, ['contain' => ['Examinations']]);
    	$this->field('examination_centre_id', ['type' => 'readonly', 'value' => $examCentre->id, 'attr' => ['value' => $examCentre->code_name]]);
    	$this->field('institution_id', ['examination_id' => $examCentre->examination_id, 'academic_period_id' => $examCentre->academic_period_id, 'education_grade_id' => $examCentre->examination->education_grade_id]);
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
    	if ($action == 'add') {
    		$options = $this->Institutions->find()
    			->find('list')
                ->innerJoinWith('InstitutionGrades', function ($q) use ($attr) {
                    return $q->where(['InstitutionGrades.education_grade_id' => $attr['education_grade_id']]);
                })
    			->leftJoin(['ExaminationCentresInstitutions' => 'examination_centres_institutions'], [
    				'ExaminationCentresInstitutions.institution_id = '.$this->Institutions->aliasField('id')
    			])
    			->leftJoin(['ExaminationCentres' => 'examination_centres'], [
    				'ExaminationCentres.id = ExaminationCentresInstitutions.examination_centre_id',
    				'ExaminationCentres.examination_id' => $attr['examination_id']
    			])
    			->where(['ExaminationCentresInstitutions.institution_id IS NULL', $this->Institutions->aliasField('is_academic') => 1])
    			->group([$this->Institutions->aliasField('id')])
    			->toArray();

    		$attr['options'] = $options;
    		$attr['type'] = 'select';

    		return $attr;
    	}
    }
}
