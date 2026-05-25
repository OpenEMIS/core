<?php
namespace Examination\Model\Table;

use App\Model\Table\AppTable;

/**
 * POCOR-9236
 * Legacy table name for import mappings and older references.
 * The physical table is examination_subjects (see ExaminationSubjectsTable).
 */
class ExaminationItemsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('examination_subjects');
        parent::initialize($config);
    }
}
