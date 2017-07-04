<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;

class EducationSubjectsTable extends ControllerActionTable
{
    use HtmlTrait;

	public function initialize(array $config)
    {
		parent::initialize($config);
		$this->addBehavior('Education.Setup');
		$this->hasMany('InstitutionSubjects',			['className' => 'Institution.InstitutionSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSubjectStudents',	['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'dependent' => true]);
		$this->belongsToMany('EducationGrades', [
			'className' => 'Education.EducationGrades',
			'joinTable' => 'education_grades_subjects',
			'foreignKey' => 'education_subject_id',
			'targetForeignKey' => 'education_grade_id',
			'through' => 'Education.EducationGradesSubjects',
			'dependent' => true
		]);
        $this->belongsToMany('FieldOfStudies', [
            'className' => 'Education.EducationFieldOfStudies',
            'joinTable' => 'education_subjects_field_of_studies',
            'foreignKey' => 'education_subject_id',
            'targetForeignKey' => 'education_field_of_study_id',
            'through' => 'Education.EducationSubjectsFieldOfStudies',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->setDeleteStrategy('restrict');
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('code', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ]);
        return $validator;
    }

    public function getEducationSubjectsByGrades($gradeId)
    {
        if ($gradeId) {
            $subjectOptions = $this
                        ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                        ->find('visible')
                        ->innerJoin(['EducationGradesSubjects' => 'education_grades_subjects'], [
                            'EducationGradesSubjects.education_subject_id = '.$this->aliasField('id'),
                            'EducationGradesSubjects.education_grade_id' => $gradeId
                        ])
                        ->order([$this->aliasField('order') => 'ASC'])
                        ->toArray();

            return $subjectOptions;
        }
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        // To handle when delete all subjects
        if (!array_key_exists('field_of_studies', $data[$this->alias()])) {
            $data[$this->alias()]['field_of_studies'] = [];
        }

        $newOptions['associated'] = ['FieldOfStudies._joinData'];

        $arrayOptions = $options->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $options->exchangeArray($arrayOptions);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->field('field_of_studies', ['after' => 'visible', 'entity' => $entity, 'type' => 'custom_field_of_studies']);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('field_of_studies', ['after' => 'visible', 'entity' => $entity, 'type' => 'custom_field_of_studies']);
    }

    public function getFieldOfStudiesOptions()
    {
        $EducationFieldOfStudies = TableRegistry::get('Education.EducationFieldOfStudies');

        $fieldOfStudiesOptions = $EducationFieldOfStudies
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $fieldOfStudiesOptions;
    }

    public function onGetCustomFieldOfStudiesElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $cellCount = 0;
        $tableHeaders = [__('Field of Studies')];
        $tableCells = [];

        $fieldOfStudiesOptions = $this->getFieldOfStudiesOptions();

        $alias = $this->alias();
        $fieldKey = 'field_of_studies';

        if ($action == 'view') {
            if (isset($entity->id)) {
                $educationSubjectId = $entity->id;
                $educationFieldOfStudies = $this->getFieldOfStudies($educationSubjectId);

                foreach ($educationFieldOfStudies as $key => $obj) {
                    $rowData = [];
                    $rowData[] = $obj['education_field_of_study']['name'];
                    $tableCells[] = $rowData;
                }
            }
        } else if ($action == 'add' || $action == 'edit') {
            $tableHeaders[] = __('Action');

            $Form = $event->subject()->Form;
            $Form->unlockField($alias.".".$fieldKey);

            $arrayOptions = [];
            if ($this->request->is(['get'])) {
                if (isset($entity->id)) {
                    $educationSubjectId = $entity->id;
                    $educationFieldOfStudies = $this->getFieldOfStudies($educationSubjectId);

                    foreach ($educationFieldOfStudies as $key => $obj) {
                        $arrayOptions[] = [
                            'id' => $obj['education_field_of_study']['id'],
                            'name' => $obj['education_field_of_study']['name'],
                            'education_programme_orientation_id' => $obj['education_field_of_study']['education_programme_orientation_id'],
                        ];
                    }
                }
            } elseif ($this->request->is(['post', 'put'])) {
                $requestData = $this->request->data;
                if (array_key_exists('field_of_studies', $requestData[$this->alias()])) {
                    foreach ($requestData[$this->alias()]['field_of_studies'] as $key => $obj) {
                        $arrayOptions[] = [
                            'id' => $obj['id'],
                            'name' => $obj['name'],
                            'education_programme_orientation_id' => $obj['education_programme_orientation_id']
                        ];
                    }
                }
            }

            foreach ($arrayOptions as $key => $obj) {
                $fieldPrefix = $attr['model'] . '.field_of_studies.' . $cellCount++;
                $joinDataPrefix = $fieldPrefix . '._joinData';

                $cellData = "";
                $cellData .= $Form->hidden($fieldPrefix.".id", ['value' => $obj['id']]);
                $cellData .= $Form->hidden($fieldPrefix.".name", ['value' => $obj['name']]);
                $cellData .= $Form->hidden($fieldPrefix.".education_programme_orientation_id", ['value' => $obj['education_programme_orientation_id']]);


                $rowData = [];
                $rowData[] = $obj['name'] . $cellData;
                // do checking to disable delete button when used in staff qualification
                // if ($obj['id'] == 1) {
                //     $rowData[] = 'cannot delete';
                // } else {
                $rowData[] = $this->getDeleteButton(['onclick' => 'jsTable.doRemove(this); $(\'#reload\').click();']);
                // }

                $tableCells[] = $rowData;

                unset($fieldOfStudiesOptions[$obj['id']]);
            }

            if (!empty($fieldOfStudiesOptions)) {
                $fieldOfStudiesOptions = [0 => '-- '.__('Select').' --'] + $fieldOfStudiesOptions;
            } else {
                $fieldOfStudiesOptions = ['' => $this->getMessage('general.select.noOptions')];
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        $attr['fieldOfStudiesOptions'] = $fieldOfStudiesOptions;

        return $event->subject()->renderElement($fieldKey, ['attr' => $attr]);
    }

    public function getFieldOfStudies($educationSubjectId)
    {
        $EducationSubjectsFieldOfStudies = TableRegistry::get('Education.EducationSubjectsFieldOfStudies');

        return $EducationSubjectsFieldOfStudies
            ->find('all')
            ->contain(['EducationFieldOfStudies'])
            ->where([$EducationSubjectsFieldOfStudies->aliasField('education_subject_id') => $educationSubjectId])
            ->toArray();
    }


    public function addEditOnAddFieldOfStudy(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'selected_field_of_study';

        if (array_key_exists($alias, $data) && array_key_exists($fieldKey, $data[$alias])) {
            $selectedFieldOfStudy = $data[$alias][$fieldKey];
            $fieldOfStudyEntity = $this->FieldOfStudies->get($selectedFieldOfStudy);

            $data[$alias]['field_of_studies'][] = [
                'id' => $selectedFieldOfStudy,
                'name' => $fieldOfStudyEntity->name,
                'education_programme_orientation_id' => $fieldOfStudyEntity->education_programme_orientation_id
            ];

            unset($data[$alias][$fieldKey]);
        }

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'FieldOfStudies' => ['validate' => false]
        ];
    }
}