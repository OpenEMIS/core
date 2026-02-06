<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;

class AttachmentTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('scholarship_attachment_types');
        parent::initialize($config);

        $this->hasMany('ApplicationAttachments', ['className' => 'Scholarship.ApplicationAttachments', 'foreignKey' => 'scholarship_attachment_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('Scholarships', [
            'className' => 'Scholarship.Scholarships',
            'joinTable' => 'scholarships_scholarship_attachment_types',
            'foreignKey' => 'scholarship_attachment_type_id',
            'targetForeignKey' => 'scholarship_id',
            'through' => 'Scholarship.ScholarshipsScholarshipAttachmentTypes',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');
    }

    public function findAvailableAttachmentTypes(Query $query, array $options)
    {
        // Extract applicant_id and scholarship_id from options, if available
        $applicantId = $options['applicant_id'] ?? null;
        $scholarshipId = $options['scholarship_id'] ?? null;

        // Load ApplicationAttachments and fetch existing attachment type IDs
        $ApplicationAttachmentsTable = TableRegistry::getTableLocator()->get('Scholarship.ApplicationAttachments');
        $existingAttachmentTypeIds = $ApplicationAttachmentsTable->find()
            ->where([
                'applicant_id' => $applicantId,
                'scholarship_id' => $scholarshipId
            ])
            ->extract('scholarship_attachment_type_id')
            ->toArray();

        // Load ScholarshipsScholarshipAttachmentTypes and join with AttachmentTypes
        $ScholarshipsScholarshipAttachmentTypesTable = TableRegistry::getTableLocator()->get('Scholarship.ScholarshipsScholarshipAttachmentTypes');
        $query
            ->select([
                'id' => $this->aliasField('id'),
                'name' => $this->aliasField('name'),
                'is_mandatory' => $ScholarshipsScholarshipAttachmentTypesTable->aliasField('is_mandatory')
            ])
            ->leftJoin(
                [$ScholarshipsScholarshipAttachmentTypesTable->getAlias() => $ScholarshipsScholarshipAttachmentTypesTable->getTable()],
                [
                    $ScholarshipsScholarshipAttachmentTypesTable->aliasField('scholarship_attachment_type_id') . ' = ' . $this->aliasField('id'),
                    $ScholarshipsScholarshipAttachmentTypesTable->aliasField('scholarship_id') => $scholarshipId
                ]
            )
            ->find('visible') 
            ->find('order');  
        // Exclude already existing attachment types
        if (!empty($existingAttachmentTypeIds)) {
            $query->where([$this->aliasField('id NOT IN') => $existingAttachmentTypeIds]);
        }

        return $query; // Ensure the query is returned
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
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
