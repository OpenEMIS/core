<?php

namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;
use Cake\Utility\Text;

class CopyReportCardShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start Report Card Copy Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];

        // POCOR-9872: no "target period must be empty" gate any more - cards skipped on an earlier run
        // (eg grade missing in the target education structure) must be copyable on a re-run;
        // copyProcess() skips each card whose code already exists in the target period instead
        $this->copyProcess($copyFrom, $copyTo);
        $this->out('End Report Card Copy Shell');
    }
    private function copyProcess($copyFrom, $copyTo)
    {
        $ReportCardTable = TableRegistry::getTableLocator()->get('ReportCard.ReportCards');
        $ReportCardExcludedSecurityRolesTable = TableRegistry::getTableLocator()->get('ReportCard.ReportCardExcludedSecurityRoles');
        $ReportCardSubjectsTable = TableRegistry::getTableLocator()->get('ReportCard.ReportCardSubjects');
        $ReportCardData = $ReportCardTable->find()->where([$ReportCardTable->aliasField('academic_period_id') => $copyFrom])->toArray();
        $connection = ConnectionManager::get('default');
        $educationGradeList = $this->getEducationGradeMapping($copyTo);//POCOR-9872
        $periodShift = $this->getPeriodShift($copyFrom, $copyTo);

        foreach($ReportCardData as $key=>$ReportCard){
            $alreadyCopied = $ReportCardTable->exists([
                $ReportCardTable->aliasField('code') => $ReportCard->code,
                $ReportCardTable->aliasField('academic_period_id') => $copyTo
            ]);
            if ($alreadyCopied) {
                $this->out('Skipped report card "' . $ReportCard->code . '" (id ' . $ReportCard->id . '): code already exists in academic period ' . $copyTo);
                continue;
            }
            // POCOR-9872: a report card whose grade has no match in the target period used to insert
            // a NULL education_grade_id, abort the whole copy and leave nothing in the new period
            $newGradeId = $this->resolveEducationGrade($educationGradeList, $ReportCard->education_grade_id);
            if (is_null($newGradeId)) {
                $this->out('Skipped report card "' . $ReportCard->code . '" (id ' . $ReportCard->id . '): education grade ' . $ReportCard->education_grade_id . ' has no matching grade in academic period ' . $copyTo);
                continue;
            }
            try{
                $connection->begin();
                //Report Card Copy Start
                $statement1 = $connection->prepare('INSERT INTO report_cards(code, name, description,start_date, end_date,generate_start_date,
                                        generate_end_date,principal_comments_required,homeroom_teacher_comments_required,teacher_comments_required,
                                        excel_template_name, excel_template,pdf_page_number,academic_period_id,education_grade_id,modified_user_id,
                                        modified, created_user_id, created)
                                        VALUES (:code, :name, :description,:start_date,:end_date,:generate_start_date,:generate_end_date,
                                        :principal_comments_required,:homeroom_teacher_comments_required,:teacher_comments_required,:excel_template_name, 
                                        :excel_template,:pdf_page_number,:academic_period_id,:education_grade_id,:modified_user_id,:modified,
                                        :created_user_id, :created)');

                $statement1->execute(array("code" => $ReportCard->code,
                                "name"=> $ReportCard->name,
                                "description"=> $ReportCard->description,
                                "start_date"=>  $this->shiftDate($ReportCard->start_date, $periodShift, "Y-m-d"),
                                "end_date"=>  $this->shiftDate($ReportCard->end_date, $periodShift, "Y-m-d"),
                                "generate_start_date"=> $this->shiftDate($ReportCard->generate_start_date, $periodShift, "Y-m-d H:i:s"),
                                "generate_end_date"=> $this->shiftDate($ReportCard->generate_end_date, $periodShift, "Y-m-d H:i:s"),
                                "principal_comments_required"=> $ReportCard->principal_comments_required,
                                "homeroom_teacher_comments_required"=> $ReportCard->homeroom_teacher_comments_required,
                                "teacher_comments_required"=> $ReportCard->teacher_comments_required,
                                "excel_template_name"=> $ReportCard->excel_template_name,
                                "excel_template"=>$this->readBlob($ReportCard->excel_template),
                                "pdf_page_number"=>$ReportCard->pdf_page_number,
                                "academic_period_id"=>$copyTo,
                                "education_grade_id"=> $newGradeId,
                                "modified_user_id"=>$ReportCard->modified_user_id,
                                "modified" => date("Y-m-d H:i:s", strtotime($ReportCard->modified)),
                                "created_user_id"=>$ReportCard->created_user_id,
                                "created" => date("Y-m-d H:i:s", strtotime($ReportCard->created))
                ));

                $newReportCardId = $connection->execute('SELECT LAST_INSERT_ID()')->fetch('assoc')['LAST_INSERT_ID()'];

                if($newReportCardId != 0) {
                    //Report Card Excluded Security Roles Copy Start 
                    $ExistingSecurityRoleData = $ReportCardExcludedSecurityRolesTable->find()
                                            ->where([$ReportCardExcludedSecurityRolesTable->aliasField('report_card_id') => $ReportCard->id])
                                            ->toArray();
                    if(!empty($ExistingSecurityRoleData)){
                        foreach($ExistingSecurityRoleData as $security_role_key=>$SecurityRoleData){
                            $statement2 = $connection->prepare('INSERT INTO report_card_excluded_security_roles(report_card_id, security_role_id) 
                                                            VALUES (:report_card_id,:security_role_id)');
                            $statement2->execute(array(
                                "report_card_id" => $newReportCardId,
                                "security_role_id" => $SecurityRoleData->security_role_id,
                            ));
                        }
                    }
                    //Report Card Excluded Security Roles Copy End

                    //Report Card Subjects Start
                    $ExistingSubjectData = $ReportCardSubjectsTable->find()
                                            ->where([$ReportCardSubjectsTable->aliasField('report_card_id') => $ReportCard->id])
                                            ->toArray();
                    if(!empty($ExistingSubjectData)){
                        foreach ($ExistingSubjectData as $subject_key => $SubjectData) {
                            $statement3 = $connection->prepare('INSERT INTO report_card_subjects(id,report_card_id,education_subject_id,education_grade_id,
                                                            created_user_id,created) VALUES (:id,:report_card_id,:education_subject_id,:education_grade_id,
                                                            :created_user_id,:created)');
                            $statement3->execute(array(
                                "id" => Text::uuid(),
                                "report_card_id" => $newReportCardId,
                                "education_subject_id" => $SubjectData->education_subject_id,
                                "education_grade_id" => $this->resolveEducationGrade($educationGradeList, $SubjectData->education_grade_id) ?? $newGradeId,
                                "created_user_id" => $SubjectData->created_user_id,
                                "created" => date("Y-m-d H:i:s", strtotime($SubjectData->created)),
                            ));
                        } 
                    }
                    //Report Card Subjects End
                }
                //Report Card Copy End
                $connection->commit();
                $this->out('Copied report card "' . $ReportCard->code . '" (id ' . $ReportCard->id . ' => ' . $newReportCardId . ')');
            } catch (\Exception $e) {
                $connection->rollback();
                // out() instead of pr()/err(): the copy page only redirects stdout into logs/CopyReportCard_copy.log
                $this->out('Failed to copy report card "' . $ReportCard->code . '" (id ' . $ReportCard->id . '): ' . $e->getMessage());
            }
        }
    }

    /**
     * POCOR-9872
     * Maps target grades of the copyTo period by programme + grade name, grade name and grade code
     */
    private function getEducationGradeMapping($copyTo)
    {
        $connection = ConnectionManager::get('default');
        $rows = $connection->execute('SELECT education_grades.id grade_id, education_grades.code grade_code, education_grades.name grade_name, education_programmes.name programme_name
                                    FROM education_grades
                                    INNER JOIN education_programmes ON education_grades.education_programme_id = education_programmes.id
                                    INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
                                    INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
                                    INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
                                    WHERE education_systems.academic_period_id = :copyTo
                                    ORDER BY education_levels.order ASC, education_cycles.order ASC, education_programmes.order ASC, education_grades.order ASC',
                                    ['copyTo' => $copyTo])->fetchAll('assoc');

        $mapping = ['programme_grade' => [], 'name' => [], 'code' => [], 'resolved' => []];
        foreach ($rows as $row) {
            // keep the first match, same as the previous name-only join
            $mapping['programme_grade'] += [$row['programme_name'] . '|' . $row['grade_name'] => $row['grade_id']];
            $mapping['name'] += [$row['grade_name'] => $row['grade_id']];
            $mapping['code'] += [$row['grade_code'] => $row['grade_id']];
        }
        return $mapping;
    }

    /**
     * Source grade is looked up by id directly, so report cards pointing to a grade outside
     * the copyFrom education structure are still matched
     */
    private function resolveEducationGrade(array &$mapping, $sourceGradeId)
    {
        if (empty($sourceGradeId)) {
            return null;
        }
        if (array_key_exists($sourceGradeId, $mapping['resolved'])) {
            return $mapping['resolved'][$sourceGradeId];
        }
        $source = ConnectionManager::get('default')->execute('SELECT education_grades.code grade_code, education_grades.name grade_name, education_programmes.name programme_name
                                    FROM education_grades
                                    INNER JOIN education_programmes ON education_grades.education_programme_id = education_programmes.id
                                    WHERE education_grades.id = :id', ['id' => $sourceGradeId])->fetch('assoc');

        $targetGradeId = null;
        if (!empty($source)) {
            $targetGradeId = $mapping['programme_grade'][$source['programme_name'] . '|' . $source['grade_name']]
                ?? $mapping['name'][$source['grade_name']]
                ?? $mapping['code'][$source['grade_code']]
                ?? null;
        }
        $mapping['resolved'][$sourceGradeId] = $targetGradeId;
        return $targetGradeId;
    }

    /**
     * POCOR-9872: copied dates used to keep the source year, so every copied template failed the
     * "within academic period" validation on edit. Dates are moved by the gap between the two
     * period start dates (calendar interval, so 08 Sep stays 08 Sep) and kept inside the target period
     */
    private function getPeriodShift($copyFrom, $copyTo)
    {
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $from = $AcademicPeriods->get($copyFrom);
        $to = $AcademicPeriods->get($copyTo);
        $fromStart = new \DateTime($from->start_date->format('Y-m-d'));
        $toStart = new \DateTime($to->start_date->format('Y-m-d'));

        return [
            'interval' => $fromStart->diff($toStart),
            'min' => $to->start_date->format('Y-m-d'),
            'max' => $to->end_date->format('Y-m-d')
        ];
    }

    private function shiftDate($value, array $periodShift, $format)
    {
        if (empty($value)) {
            return null;
        }
        $date = is_object($value) && method_exists($value, 'format')
            ? new \DateTime($value->format('Y-m-d H:i:s'))
            : new \DateTime(date('Y-m-d H:i:s', strtotime((string)$value)));
        $date->add($periodShift['interval']);

        $day = $date->format('Y-m-d');
        if ($day < $periodShift['min']) {
            $date->modify($periodShift['min'] . ' ' . $date->format('H:i:s'));
        } elseif ($day > $periodShift['max']) {
            $date->modify($periodShift['max'] . ' ' . $date->format('H:i:s'));
        }
        return $date->format($format);
    }

    /**
     * POCOR-9872
     */
    private function readBlob($value)
    {
        if (is_resource($value)) {
            return stream_get_contents($value, -1, 0);
        }
        return $value;
    }
}
