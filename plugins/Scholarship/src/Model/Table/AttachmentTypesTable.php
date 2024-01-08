<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
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
        $applicantId = array_key_exists('applicant_id', $options) ? $options['applicant_id'] : null;
        $scholarshipId = array_key_exists('scholarship_id', $options) ? $options['scholarship_id'] : null;

        $ApplicationAttachmentsTable = TableRegistry::get('Scholarship.ApplicationAttachments');
        $existingAttachmentTypeIds = $ApplicationAttachmentsTable->find()      
            ->where([
                $ApplicationAttachmentsTable->aliasField('applicant_id') => $applicantId,
                $ApplicationAttachmentsTable->aliasField('scholarship_id') => $scholarshipId
            ])
            ->extract('scholarship_attachment_type_id')
            ->toArray();

        $ScholarshipsScholarshipAttachmentTypesTable = TableRegistry::get('Scholarship.ScholarshipsScholarshipAttachmentTypes');
        $query
            ->select(['is_mandatory' => $ScholarshipsScholarshipAttachmentTypesTable->aliasField('is_mandatory')])
            ->find('visible')
            ->find('order')
            ->innerJoin(
                [$ScholarshipsScholarshipAttachmentTypesTable->alias() => $ScholarshipsScholarshipAttachmentTypesTable->table()],
                [
                    $ScholarshipsScholarshipAttachmentTypesTable->aliasField('scholarship_attachment_type_id = ') . $this->aliasField('id'),
                    $ScholarshipsScholarshipAttachmentTypesTable->aliasField('scholarship_id') => $scholarshipId
                ]
            )
            ->autoFields(true);

        if ($existingAttachmentTypeIds) {
            $query->where([$this->aliasField('id NOT IN') => $existingAttachmentTypeIds]);
        }

        return $query;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
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
