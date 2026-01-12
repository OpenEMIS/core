<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class QualificationSpecialisationsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);

        $this->belongsToMany('Qualifications', [
            'className' => 'Staff.Qualifications',
            'joinTable' => 'staff_qualifications_specialisations',
            'foreignKey' => 'qualification_specialisation_id',
            'targetForeignKey' => 'staff_qualification_id',
            'through' => 'Staff.QualificationsSpecialisations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        
        $this->addBehavior('FieldOption.FieldOption');

        $this->setDeleteStrategy('restrict');
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('education_field_of_study_id');
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('education_field_of_study_id', ['type' => 'select']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
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

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'name', 'education_field_of_study_id', 'default', 'international_code', 'national_code'
        ]);
    } 
    
    public function onUpdateFieldEducationFieldOfStudyId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $parentFieldOptions = $this->EducationFieldOfStudies->find('list')->toArray();
            $selectedParentFieldOption = $this->queryString('parent_field_option_id', $parentFieldOptions);

            $attr['options'] = $parentFieldOptions;
        }
        return $attr;
    }
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
            case 'visible':
                return __('Visible');
            case 'name':
                return __('Name');
            case 'international_code':
                return __('International Code');
            case 'national_code':
                return __('National Code');
            case 'editable':
                return __('Editable');
            case 'default':
                return __('Default');
            case 'education_field_of_study_id':  
                return __('Education Field Of Study');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
