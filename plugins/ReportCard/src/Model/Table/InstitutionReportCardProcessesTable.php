<?php
namespace ReportCard\Model\Table;

use App\Model\Table\ControllerActionTable;

class InstitutionReportCardProcessesTable extends ControllerActionTable
{
    const NEW_PROCESS = 1;
    const RUNNING = 2;
    const COMPLETED = 3;
    const ERROR = -1;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('ReportCards', ['className' => 'ReportCard.ReportCards']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
    }
}
