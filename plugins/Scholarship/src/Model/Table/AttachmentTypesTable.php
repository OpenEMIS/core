<?php
namespace Scholarship\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class AttachmentTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('scholarship_attachment_types');
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
}
