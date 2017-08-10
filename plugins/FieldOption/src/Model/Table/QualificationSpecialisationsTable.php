<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class QualificationSpecialisationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('Qualifications', ['className' => 'Staff.Qualifications']);
        $this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
        
        $this->addBehavior('FieldOption.FieldOption');

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('education_field_of_study_id');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('education_field_of_study_id', ['type' => 'select']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $parentFieldOptions = $this->EducationFieldOfStudies->find('list')->toArray();
        $selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

        if (!empty($selectedParentFieldOption)) {
            $query->where([$this->aliasField('education_field_of_study_id') => $selectedParentFieldOption]);
        }

        $this->setFieldOrder([
            'visible', 'default' ,'editable', 'name', 'education_field_of_study_id', 'international_code', 'national_code'
        ]);

        $this->controller->set(compact('parentFieldOptions', 'selectedParentFieldOption'));
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'name', 'education_field_of_study_id', 'default', 'international_code', 'national_code'
        ]);
    } 
    
    public function onUpdateFieldEducationFieldOfStudyId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $parentFieldOptions = $this->EducationFieldOfStudies->find('list')->toArray();
            $selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

            $attr['options'] = $parentFieldOptions;
        }
        return $attr;
    }
}
