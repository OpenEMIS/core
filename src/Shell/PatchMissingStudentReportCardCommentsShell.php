<?php
namespace App\Shell;

use Cake\I18n\Time;
use Cake\Datasource\ConnectionManager;
use Cake\Console\Shell;
use Cake\Log\Log;

class PatchMissingStudentReportCardCommentsShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionStudentsReportCards');
        $this->loadModel('Institution.InstitutionStudentsReportCardsComments');
    }

    public function main()
    {
        $this->patchMissingReportCardComments();
        $this->patchMissingReportCardSubjectComments();
    }

    private function patchMissingReportCardComments()
    {
        $this->out('Initialize Patch Missing Student Report Card Comments ('.Time::now().')');

        $connection = ConnectionManager::get('default');
        $results = $connection
            ->execute(
                "SELECT `Patches`.`id` AS `Patches__id` FROM `z_4701_deleted_records` `Patches` WHERE (`Patches`.`reference_table` = :reference_table AND `Patches`.`status` = :status) ORDER BY `Patches`.`id`", [
                    'reference_table' => 'Institution.InstitutionStudentsReportCards',
                    'status' => 0
                ]
            )
            ->fetchAll('assoc');

        foreach ($results as $key => $obj) {
            $deletedId = $obj['Patches__id'];

            $deletedResult = $connection
                ->execute(
                    "SELECT * FROM `z_4701_deleted_records` `Patches` WHERE `Patches`.`id` = ".$deletedId
                )
                ->fetch('assoc');

            $deletedId = $deletedResult['id'];
            $ids = json_decode($deletedResult['reference_key'], true);
            $data = json_decode($deletedResult['data'], true);

            if ($this->InstitutionStudentsReportCards->exists($ids)) {
                $entity = $this->InstitutionStudentsReportCards->find()
                    ->where($ids)
                    ->first();
            } else {
                $entity = $this->InstitutionStudentsReportCards->newEntity($ids);
            }

            if (isset($data['file_name'])) {
                $data['file_name'] = NULL;
            }
            if (isset($data['file_content'])) {
                $data['file_content'] = NULL;
            }

            $patchData = [
                'principal_comments' => $data['principal_comments'],
                'homeroom_teacher_comments' => $data['homeroom_teacher_comments'],
                'report_card_id' => $data['report_card_id'],
                'student_id' => $data['student_id'],
                'institution_id' => $data['institution_id'],
                'academic_period_id' => $data['academic_period_id'],
                'education_grade_id' => $data['education_grade_id'],
                'institution_class_id' => $data['institution_class_id']
            ];
            $entity = $this->InstitutionStudentsReportCards->patchEntity($entity, $patchData);

            if ($this->InstitutionStudentsReportCards->save($entity)) {
                $connection->update('z_4701_deleted_records', [
                    'status' => 1
                ], [
                    'id' => $deletedId
                ]);
            }

            $this->out('Patch Missing Student Report Card Comments Id: '.$deletedId);
        }

        $this->out('End Patch Missing Student Report Card Comments ('.Time::now().')');
    }

    private function patchMissingReportCardSubjectComments()
    {
        $this->out('Initialize Patch Missing Student Report Card Subject Comments ('.Time::now().')');

        $connection = ConnectionManager::get('default');
        $results = $connection
            ->execute(
                "SELECT `Patches`.`id` AS `Patches__id` FROM `z_4701_deleted_records` `Patches` WHERE (`Patches`.`reference_table` = :reference_table AND `Patches`.`status` = :status) ORDER BY `Patches`.`id`", [
                    'reference_table' => 'Institution.StudentsReportCardsComments',
                    'status' => 0
                ]
            )
            ->fetchAll('assoc');

        foreach ($results as $key => $obj) {
            $deletedId = $obj['Patches__id'];

            $deletedResult = $connection
                ->execute(
                    "SELECT * FROM `z_4701_deleted_records` `Patches` WHERE `Patches`.`id` = ".$deletedId
                )
                ->fetch('assoc');

            $deletedId = $deletedResult['id'];
            $ids = json_decode($deletedResult['reference_key'], true);
            $data = json_decode($deletedResult['data'], true);

            if ($this->InstitutionStudentsReportCardsComments->exists($ids)) {
                $entity = $this->InstitutionStudentsReportCardsComments->find()
                    ->where($ids)
                    ->first();
            } else {
                $entity = $this->InstitutionStudentsReportCardsComments->newEntity($ids);
            }

            $patchData = [
                'comments' => $data['comments'],
                'report_card_comment_code_id' => $data['report_card_comment_code_id'],
                'report_card_id' => $data['report_card_id'],
                'student_id' => $data['student_id'],
                'institution_id' => $data['institution_id'],
                'academic_period_id' => $data['academic_period_id'],
                'education_grade_id' => $data['education_grade_id'],
                'education_subject_id' => $data['education_subject_id'],
                'staff_id' => $data['staff_id']
            ];
            $entity = $this->InstitutionStudentsReportCardsComments->patchEntity($entity, $patchData);

            if ($this->InstitutionStudentsReportCardsComments->save($entity)) {
                $connection->update('z_4701_deleted_records', [
                    'status' => 1
                ], [
                    'id' => $deletedId
                ]);
            }

            $this->out('Patch Missing Student Report Card Comments Id: '.$deletedId);
        }

        $this->out('End Patch Missing Student Report Card Subject Comments ('.Time::now().')');
    }
}
