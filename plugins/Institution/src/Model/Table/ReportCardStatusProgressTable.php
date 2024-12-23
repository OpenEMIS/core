<?php
namespace Institution\Model\Table;

use ArrayObject;
use stdClass;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Datasource\ResultSetInterface;

class ReportCardStatusProgressTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config): void
    {
        $this->setTable('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->hasMany('ClassStudents', ['className' => 'Institution.InstitutionClassStudents', 'saveStrategy' => 'replace', 'cascadeCallbacks' => true]);


        $this->belongsToMany('Students', [
            'className' => 'User.Users',
            'through' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'student_id',
        ]);


        // this behavior restricts current user to see All Classes or My Classes
        $this->addBehavior('Security.SecurityAccess');
        $this->addBehavior('Security.InstitutionClass');
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Restful.RestfulAccessControl', [

            'Results'=> ['index']
        ]);

        $this->addBehavior('Institution.InstitutionTab');
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if ($data->offsetExists('classStudents') && empty($data['classStudents'])) { //only utilize save by association when class student empty.
            $data['class_students'] = [];
            $data['total_male_students'] = 0;
            $data['total_female_students'] = 0;
            $data->offsetUnset('classStudents');
        }
    }


    /******************************************************************************************************************
    **
    ** index action methods
    **
    ******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Academic Periods filter
        $institutionId  = $this->getInstitutionID();
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        $academicPeriodId = $this->request->getQuery('academic_period_id');
        $reportCardId = $this->request->getQuery('report_card_id');


        $selectedAcademicPeriod = !is_null($this->request->getQuery('academic_period_id')) ? $this->request->getQuery('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $reportCardTable = TableRegistry::get('ReportCard.ReportCards');
        $reportCardOptions = $reportCardTable
                        ->find('list',[
                            'keyField' => 'id',
                            'valueField' => 'name'
                            ])
                        ->where(['academic_period_id'=>$selectedAcademicPeriod])
                        ->disableHydration() // POCOR-8533
                        ->toArray();

        $reportCardOptions = ['-1' => '-- '.__('Select Report Card').' --'] + $reportCardOptions;
        $selectedReportCard = !is_null($this->request->getQuery('report_card_id')) ? $this->request->getQuery('report_card_id') : -1;
        $this->controller->set(compact('academicPeriodOptions', 'selectedAcademicPeriod', 'institutionId', 'reportCardOptions', 'selectedReportCard'));

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $selectedClass = !is_null($this->request->getQuery('class_id')) ? $this->request->getQuery('class_id') : 'all';
        $classOptions = [];
        $classLists = [];
        if ($selectedReportCard != -1) {
            $reportCardEntity = $reportCardTable->find()->where(['id' => $selectedReportCard])->first();
            if (!empty($reportCardEntity)) {
                $classOptions = $classLists = $Classes->find('list')
                    ->matching('ClassGrades')
                    ->where([
                        $Classes->aliasField('academic_period_id') => $selectedAcademicPeriod,
                        $Classes->aliasField('institution_id') => $institutionId,
                        'ClassGrades.education_grade_id' => $reportCardEntity->education_grade_id
                    ])
                    ->order([$Classes->aliasField('name')])
                    ->toArray();
            } else {
                // if selected report card is not valid, do not show any students
                $selectedClass = 'all';
            }
        }

        $classOptions['all']   = "All Classes" ;

       // echo $selectedClass; die;
        $classOptions = ['-1' => '-- '.__('Select Class').' --'] + $classOptions;
        $this->controller->set(compact('classOptions', 'selectedClass'));


        $reportCardProcesses = TableRegistry::get('ReportCard.ReportCardProcesses');
        $institutionStudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $classIds = 0;
        if(!empty($classLists)){
            $classIds = array_keys($classLists);
        }
        $query
                ->select([
                    'id','name','institution_id',
                    //POCOR-6692
                    'inProcess' => $reportCardProcesses->find()->where([
                                'report_card_id IS' => $reportCardId,
                                'academic_period_id IS' => $academicPeriodId,
                                'institution_id IS' => $institutionId,
                            ])->count(),
                    /*'inCompleted' => $institutionStudentsReportCards->find()->where([
                                'report_card_id' => $reportCardId,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                                'status' => 3
                            ])->count()*/
                ])
                ->where([
                    $this->aliasField('academic_period_id IS') => $academicPeriodId,
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('id IN') => $classIds
                    ])
                ->formatResults(function (ResultSetInterface $results) use ($reportCardId, $institutionId, $academicPeriodId) {
                    return $results->map(function ($row) use ($reportCardId, $institutionId, $academicPeriodId) {
                        $institutionStudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
                        $inCompleted = $institutionStudentsReportCards->find()->where([
                            $institutionStudentsReportCards->aliasField('report_card_id') => $reportCardId,
                            $institutionStudentsReportCards->aliasField('academic_period_id') => $academicPeriodId,
                            $institutionStudentsReportCards->aliasField('institution_id') => $institutionId,
                            $institutionStudentsReportCards->aliasField('institution_class_id') => $row['id'],
                            $institutionStudentsReportCards->aliasField('status') => 3
                        ])->count();
                        $row['inCompleted'] = $inCompleted;
                       //echo "<pre>"; print_r($row);echo "</pre>";
                        return $row;
                    });

            });

    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
        $searchableFields[] = 'openemis_no';
    }

    public function onGetReportCard(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('report_card_id')) {
            $reportCardId = $entity->report_card_id;
        } else if (!is_null($this->request->getQuery('report_card_id'))) {
            // used if student report card record has not been created yet
            $reportCardId = $this->request->getQuery('report_card_id');
        }

        if (!empty($reportCardId)) {
            $reportCardEntity = $this->ReportCards->find()->where(['id' => $reportCardId])->first();
            if (!empty($reportCardEntity)) {
                $value = $reportCardEntity->code_name;
            }
        }
        return $value;
    }

}
