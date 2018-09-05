<?php
namespace StaffAppraisal\Model\Table;

use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Datasource\EntityInterface;

class AppraisalFormsCriteriasScoresTable extends AppTable
{
    // Added
    const FIELD_TYPE_SCORE = "SCORE";

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasOne('AppraisalFormsCriterias', [
            'className' => 'StaffAppraisal.AppraisalFormsCriterias',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('AppraisalFormsCriteriasScoresLinks', [
            'className' => 'StaffAppraisal.AppraisalFormsCriteriasScoresLinks',
            'foreignKey' => ['appraisal_form_id', 'appraisal_criteria_id'],
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->belongsTo('AppraisalCriterias', [
            'className' => 'StaffAppraisal.AppraisalCriterias',
            'foreignKey' => [
                'appraisal_form_id', 
                'appraisal_criteria_id'
            ]
        ]);
    }

    // Added
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Appraisal.add.afterSave'] = 'appraisalScoreAddAfterSave';
        $events['Model.Appraisal.edit.beforeSave'] = 'appraisalScoreEditBeforeSave';
        // $events['Model.Appraisal.beforeDelete'] = 'appraisalScoreBeforeDelete';
        return $events;
    }

    // Added
    public function appraisalScoreAddAfterSave(Event $event, Entity $entity, ArrayObject $requestData, $alias)
    {
        // Form ID
        $requestData[$this->alias()]['id'] = $entity->id;

        $this->createAppraisalFormsCriteriasScoresRecord($requestData);
    }

    public function appraisalScoreEditBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, $alias)
    {
        // Form ID
        $requestData[$this->alias()]['id'] = $entity->id;

        $this->createAppraisalFormsCriteriasScoresRecord($requestData);
    }

    // public function appraisalScoreBeforeDelete(Event $event, Entity $entity)
    // {
    //     $formId = $entity->id;

    //     // Get all the criterias based on the form
    //     $appraisalFormsCriterias = TableRegistry::get('StaffAppraisal.AppraisalFormsCriterias');
    //     $appraisalFormsCriteriasEntities = $appraisalFormsCriterias
    //         ->find()
    //         ->where([
    //             $appraisalFormsCriterias->aliasField('appraisal_form_id') => $formId
    //         ]);

    //     $criteriaToBeRemoved['AppraisalForms'] = [
    //         'id' => $entity->id,
    //         'appraisal_criterias' => $appraisalFormsCriteriasEntities->toArray()
    //     ]; 

    //     $this->deleteAppraisalFormsCriteriasScoreRecord($criteriaToBeRemoved);
    // }

    private function createAppraisalFormsCriteriasScoresRecord($requestData) 
    {
        $appraisalFormsCriteriasScores = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScores');

        if(array_key_exists("appraisal_criterias", $requestData['AppraisalForms']) && !empty($requestData['AppraisalForms']['appraisal_criterias'])) {


            // To remove the record that is un-link to the form.
            $this->deleteAppraisalFormsCriteriasScoreRecord($requestData);

            foreach ($requestData['AppraisalForms']['appraisal_criterias'] as $appraisalFormCriteriasEntity) {

                $criteriasFieldType = strtoupper($appraisalFormCriteriasEntity['_joinData']['field_type']);                

                switch ($criteriasFieldType) {
                    case self::FIELD_TYPE_SCORE:
                        // Create record at appraisal_forms_criterias_scores table
                        $formId = $requestData['AppraisalScores']['id'];
                        $criteriaId = $appraisalFormCriteriasEntity['id'];

                        // $actualDataToBeSave['appraisal_forms_criterias_scores'][] = [
                        $actualDataToBeSave[] = [
                            'appraisal_form_id' => $formId,
                            'appraisal_criteria_id' => $criteriaId,
                            'final_score' => 0
                        ];
                        break;
                }
            }

            // Process all the entities as a single transaction
            $newEntities = $appraisalFormsCriteriasScores->newEntities($actualDataToBeSave);

            $appraisalFormsCriteriasScores->connection()->transactional(function () use ($appraisalFormsCriteriasScores, $newEntities) {
                foreach ($newEntities as $entity) {
                    $appraisalFormsCriteriasScores->save($entity, ['atomic' => false]);
                }
            });
        }
    }

    private function deleteAppraisalFormsCriteriasScoreRecord($requestData)
    {
        // pr($requestData);die;
        $formId = $requestData['AppraisalForms']['id'];
        $criteriaIdFromTable = [];

        $appraisalFormsCriterias = TableRegistry::get('StaffAppraisal.AppraisalFormsCriterias');
        $appraisalFormsCriteriasScores = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScores');
        $appraisalFormsCriteriasScoresLinks = TableRegistry::get('StaffAppraisal.AppraisalFormsCriteriasScoresLinks');

        // Get all the criterias score records
        $appraisalFormCriteriasEntity = $appraisalFormsCriterias
            ->find()
            ->where([
                $appraisalFormsCriterias->aliasField('appraisal_form_id') => $formId
            ]);


        // Check the array different den remove from the database table(To unlink)
        $appraisalFormCriteriasArray = $appraisalFormCriteriasEntity->toArray();
        foreach ($appraisalFormCriteriasArray as $obj) {
            $criteriaIdFromTable[] = $obj->appraisal_criteria_id;
        }

        $appraisalFormCriteriasFromRequestData = $requestData['AppraisalForms']['appraisal_criterias'];
        foreach ($appraisalFormCriteriasFromRequestData as $obj) {
            $criteriaIdFromRequestData[] = $obj['id'];
        }        


        // pr($criteriaIdFromTable);
        // pr('-------------------------------');
        // pr($criteriaIdFromRequestData);die;

        $result = array_diff($criteriaIdFromTable,$criteriaIdFromRequestData);


        foreach ($result as $removeCriteriasId) {

            // Delete record from appraisal_forms_criterias table
            $appraisalFormCriteriasEntity = $appraisalFormsCriterias
                ->find()
                ->where([
                    $appraisalFormsCriterias->aliasField('appraisal_form_id') => $formId,
                    $appraisalFormsCriterias->aliasField('appraisal_criteria_id') => $removeCriteriasId
                ]);

            if($appraisalFormCriteriasEntity->count() > 0) {
                $appraisalFormsCriterias->delete($appraisalFormCriteriasEntity->first());
            }

            // Delete record from appraisal_forms_criterias_scores table
            $appraisalFormCriteriasScoresEntity = $appraisalFormsCriteriasScores
                ->find()
                ->where([
                    $appraisalFormsCriteriasScores->aliasField('appraisal_form_id') => $formId,
                    $appraisalFormsCriteriasScores->aliasField('appraisal_criteria_id') => $removeCriteriasId
                ]);

            if($appraisalFormCriteriasScoresEntity->count() > 0) {
                $appraisalFormsCriteriasScores->delete($appraisalFormCriteriasScoresEntity->first());
            }

            // Delete record from appraisal_forms_criterias_scores_links table
            $appraisalFormsCriteriasScoresLinksEntities = $appraisalFormsCriteriasScoresLinks
                ->find()
                ->where([
                    $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_form_id') => $formId,
                    $appraisalFormsCriteriasScoresLinks->aliasField('appraisal_criteria_id') => $removeCriteriasId
                ]);

            foreach ($appraisalFormsCriteriasScoresLinksEntities->toArray() as $appraisalFormsCriteriasScoresLinksEntity) {
                // There exists the linkage record therefore remove
                if(isset($appraisalFormsCriteriasScoresLinksEntity)) {
                    $appraisalFormsCriteriasScoresLinks->delete($appraisalFormsCriteriasScoresLinksEntity);
                }
            }

        }
    }
}
