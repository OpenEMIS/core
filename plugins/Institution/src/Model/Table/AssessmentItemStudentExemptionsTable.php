<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Core\Configure;
use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

// POCOR-8224 start
class AssessmentItemStudentExemptionsTable extends AppTable
{

    public function initialize(array $config): void
    {

        $this->setTable('assessment_item_student_exemptions');
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'AssessmentItemStudentExemptions' => ['index', 'edit', 'save', 'view'],
        ]);

    }


    public static function saveExemptions($params): void
    {
        // Log::debug($params);
        $exempt_students_base64 = $params['exempt_students'] ?? null;
        $assessment_item_id = $params['assessment_item_id'] ?? null;
        $assessment_period_id = $params['assessment_period_id'] ?? null;
        $created_user_id = $params['created_user_id'] ?? null;
        $institution_class_id = $params['institution_class_id'] ?? null;
        $type = $params['type'] ?? null;//POCOR-9042

        //POCOR-9114 -- START -- changed logic for multiple assessment periods
        $assessment_period_ids = [];
        if (!empty($params['assessment_period_id'])) {
            if (is_array($params['assessment_period_id'])) {
                $assessment_period_ids = $params['assessment_period_id'];
            } else {
                $assessment_period_ids = [$params['assessment_period_id']];
            }
        }
        //POCOR-9114 -- START

        if ($institution_class_id && $exempt_students_base64 && $assessment_item_id && $assessment_period_id && $type) {//POCOR-9042 add type
            // Decode the base64 string into an array of students
            $exempt_students = json_decode(base64_decode($exempt_students_base64), true);
//            Log::debug($exempt_students);
//            Log::debug([$assessment_item_id, $assessment_period_id, $institution_class_id]);
            // Get the table object
            $AssessmentItemStudentExemptions = self::getDynamicTableInstance('assessment_item_student_exemptions');
            $AssessmentItemsTable = self::getDynamicTableInstance('assessment_items');

            // Retrieve assessment_id and education_subject_id from assessment_item_id
            $assessmentItem = $AssessmentItemsTable->find()
                ->select(['assessment_id', 'education_subject_id'])
                ->where(['id' => $assessment_item_id])
                ->first();

            if (!$assessmentItem) {
                Log::error('Assessment item not found for ID ' . $assessment_item_id);
                return;
            }

            $assessment_id = $assessmentItem->assessment_id;
            $education_subject_id = $assessmentItem->education_subject_id;

            foreach ($assessment_period_ids as $assessment_period_id) { //POCOR-9114
                foreach ($exempt_students as $student) {
                    $student_id = $student['s_id'];
                    $education_grade_id = $student['eg_id'];  // Updated field

                    // Check if the exemption already exists
                    $existingExemption = $AssessmentItemStudentExemptions->find()
                        ->where([
                            'assessment_id' => $assessment_id,
                            'education_subject_id' => $education_subject_id,
                            'student_id' => $student_id,
                            'institution_class_id' => $institution_class_id,  // Changed from institution_class_student_id
                            'assessment_period_id' => $assessment_period_id,
                            'education_grade_id' => $education_grade_id,
                            'type' => $type//POCOR-9042
                        ])
                        ->first();

                    if (!$existingExemption) {
                        // If no existing exemption, create a new one
                        $newExemption = $AssessmentItemStudentExemptions->newEntity([
                            'assessment_id' => $assessment_id,
                            'education_subject_id' => $education_subject_id,
                            'student_id' => $student_id,
                            'institution_class_id' => $institution_class_id,
                            'assessment_period_id' => $assessment_period_id,
                            'education_grade_id' => $education_grade_id,
                            'type' => $type,//POCOR-9042
                            'created_user_id' => $created_user_id,
                            'created' => date('Y-m-d H:i:s')
                        ]);

                        if ($AssessmentItemStudentExemptions->save($newExemption)) {
    //                        Log::debug('Exemption added for student ' . $student_id);
                        }
                    }
                }
            }
        }
    }


    /**
     * @throws \Exception
     */
    public static function removeExemptions($params): void
    {
        $non_exempt_students_base64 = $params['unexempt_students'] ?? null;
        $assessment_item_id = $params['assessment_item_id'] ?? null;
        $assessment_period_id = $params['assessment_period_id'] ?? null;
        $institution_class_id = $params['institution_class_id'] ?? null;
        $type = $params['type'] ?? null; //POCOR-9042

        //POCOR-9114 -- START -- changed logic for multiple assessment periods
        $assessment_period_ids = [];
        if (!empty($params['assessment_period_id'])) {
            if (is_array($params['assessment_period_id'])) {
                $assessment_period_ids = $params['assessment_period_id'];
            } else {
                $assessment_period_ids = [$params['assessment_period_id']];
            }
        }
        //POCOR-9114 -- START

//        Log::debug([$assessment_item_id, $assessment_period_id, $institution_class_id ]);
        if ($non_exempt_students_base64 && $assessment_item_id && $assessment_period_id && $type) {//POCOR-9042 add type
            // Decode the base64 string into an array of student IDs
            $unexempt_students = json_decode(base64_decode($non_exempt_students_base64), true);
//            Log::debug($unexempt_students);

            // Get the table object
            $AssessmentItemStudentExemptions = self::getDynamicTableInstance('assessment_item_student_exemptions');
            $AssessmentItemsTable = self::getDynamicTableInstance('assessment_items');

            // Retrieve assessment_id and education_subject_id from assessment_item_id
            $assessmentItem = $AssessmentItemsTable->find()
                ->select(['assessment_id', 'education_subject_id'])
                ->where(['id' => $assessment_item_id])
                ->first();

            if (!$assessmentItem) {
                Log::error('Assessment item not found for ID ' . $assessment_item_id);
                return;
            }

            $assessment_id = $assessmentItem->assessment_id;
            $education_subject_id = $assessmentItem->education_subject_id;

            foreach ($assessment_period_ids as $assessment_period_id) {
                foreach ($unexempt_students as $student) {
                    $student_id = $student['s_id'];
                    $education_grade_id = $student['eg_id'];  // Updated field

                    // Find and delete the exemption for the student
                    $existingExemption = $AssessmentItemStudentExemptions->find()
                        ->where([
                            'assessment_id' => $assessment_id,
                            'education_subject_id' => $education_subject_id,
                            'student_id' => $student_id,
                            'assessment_period_id' => $assessment_period_id,
                            'institution_class_id' => $institution_class_id,
                            'education_grade_id' => $education_grade_id,
                            'type' => $type//POCOR-9042
                        ])
                        ->first();

                    if ($existingExemption) {
                        // If the exemption exists, delete it
                        if ($AssessmentItemStudentExemptions->delete($existingExemption)) {
                            Log::debug('Exemption removed for student ' . $student_id);
                        }
                    }
                }
            }
        }
    }


    public static function getInstitutionClassDetails($institution_class_id): \Cake\Datasource\EntityInterface
    {
        $institution_classes = self::getDynamicTableInstance('institution_classes');
        return $institution_classes->get($institution_class_id);
    }

    public static function getAssessmentDetails($assessment_id): \Cake\Datasource\EntityInterface
    {
        $assessments = self::getDynamicTableInstance('assessments');
        return $assessments->get($assessment_id);
    }

    public static function getEducationGradeDetails($education_grade_id): \Cake\Datasource\EntityInterface
    {
        $education_grades = self::getDynamicTableInstance('education_grades');
        return $education_grades->get($education_grade_id);
    }

    public static function getAcademicPeriodDetails($academic_period_id): \Cake\Datasource\EntityInterface
    {
        $academic_periods = self::getDynamicTableInstance('academic_periods');
        return $academic_periods->get($academic_period_id);
    }

    public static function getInstitutionDetails($institution_id): \Cake\Datasource\EntityInterface
    {
        $institutions = self::getDynamicTableInstance('institutions');
        return $institutions->get($institution_id);
    }

    public static function getAssessmentItems($assessment_id): array
    {
        $assessment_items_table = self::getDynamicTableInstance('assessment_items');

        $query = $assessment_items_table->find()
            ->select([
                'id' => $assessment_items_table->aliasField('id'),
                'name' => 'EducationSubjects.name',
                'classification' => 'classification'
            ])
            ->distinct([$assessment_items_table->aliasField('id')])
            ->innerJoin(
                ['EducationSubjects' => 'education_subjects'],
                ['EducationSubjects.id = ' . $assessment_items_table->aliasField('education_subject_id'),
                    'EducationSubjects.visible = 1']
            )
            ->where([$assessment_items_table->aliasField('assessment_id') => $assessment_id])
            ->order(['classification', 'EducationSubjects.name'])
            ->disableHydration();

        $result = $query->all()->toArray();
        $assessment_items = [];

        foreach ($result as $subject) {
            $classification = $subject['classification'];
            if (empty($classification)) {
                $assessment_items[$subject['id']] = [
                    'id' => $subject['id'],
                    'name' => __($subject['name']),
                    'classification' => ''
                ];
            } else {
                if (isset($assessment_items[$classification])) {
                    if (!isset($assessment_items[$classification]['ids'][$subject['id']])) {
                        $assessment_items[$classification]['ids'][] = $subject['id'];
                    }
                } else {
                    $assessment_items[$classification] = [
                        'id' => $classification,
                        'name' => __($classification),
                        'ids' => [$subject['id']],
                        'classification' => $classification
                    ];
                }
            }
        }

        return $assessment_items;
    }

    public static function getAssessmentPeriods($assessment_id): array
    {
        $assessment_periods_table = self::getDynamicTableInstance('assessment_periods');
        $query = $assessment_periods_table->find()
            ->select([
                'id' => $assessment_periods_table->aliasField('id'),
                'name' => $assessment_periods_table->aliasField('name'),
                'term' => $assessment_periods_table->aliasField('academic_term')
            ])
            ->distinct([$assessment_periods_table->aliasField('id')])
            ->innerJoin(
                ['Assessments' => 'assessments'],
                ['Assessments.id = ' . $assessment_periods_table->aliasField('assessment_id')]
            )
            ->where(['Assessments.id' => $assessment_id])
            ->order([$assessment_periods_table->aliasField('name')])
            ->disableHydration();

        $assessment_periods_raw = $query->all()->toArray();
        $assessment_periods = [];

        foreach ($assessment_periods_raw as $period) {
            $assessment_periods[$period['id']] = [
                'id' => $period['id'],
                'name' => __($period['term']) . ' - ' . __($period['name'])
            ];
        }

        return $assessment_periods;
    }

    /**
     * POCOR-8231
     * Gets a dynamic table instance with all associations.
     *
     * @param string $tableName The name of the table.
     * @return \Cake\ORM\Table The table instance.
     * @throws \Exception If the table instance cannot be retrieved.
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
//            Log::debug('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }


}
