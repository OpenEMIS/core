<?php
namespace Scholarship\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class ApplicationInstitutionChoicesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_application_institution_choices');
        parent::initialize($config);

        $this->belongsTo('Applications', ['className' => 'Scholarship.Applications', 'foreignKey' => ['applicant_id', 'scholarship_id']]);
        $this->belongsTo('Countries', ['className' => 'FieldOption.Countries', 'foreignKey' => 'country_id']);
        $this->belongsTo('InstitutionChoiceTypes', ['className' => 'Scholarship.InstitutionChoiceTypes', 'foreignKey' => 'scholarship_institution_choice_type_id']);
        $this->belongsTo('InstitutionChoiceStatuses', ['className' => 'Scholarship.InstitutionChoiceStatuses', 'foreignKey' => 'scholarship_institution_choice_status_id']);
        $this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies' , 'foreignKey' => 'education_field_of_study_id']);
        $this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels',  'foreignKey' =>'qualification_level_id' ]);
        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('country_id')
            ->add('end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'start_date', true],
                'message' => __('End Date should not be earlier than Start Date')
            ])
            ->add('estimated_cost', 'validateDecimal', [
                'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                'message' => __('Value cannot be more than two decimal places')
            ])
            ->add('scholarship_institution_choice_status_id', 'ruleCheckChoiceStatus', [
                'rule' => ['checkChoiceStatus'],
                'provider' => 'table',
                'message' => __('Please ensure that status is ACCEPTED'),
                'on' => function ($context) {
                    //trigger validation only when selection is set to 1 and edit operation
                    return (isset($context['data']['is_selected']) && $context['data']['is_selected'] == 1  && !$context['newRecord']);
                }
            ]);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->dirty('is_selected')) {
            if ($entity->is_selected == 1) {
                $this->updateAll(
                    ['is_selected' => 0],
                    [
                        'applicant_id' => $entity->applicant->id,
                        'scholarship_id' => $entity->scholarship->id,
                        'id <> ' => $entity->id
                    ]
                 );
            } 
        }
    }
}
