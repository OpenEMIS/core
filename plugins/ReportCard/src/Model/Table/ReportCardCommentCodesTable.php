<?php
namespace ReportCard\Model\Table;

use App\Model\Table\ControllerActionTable;

class ReportCardCommentCodesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->hasMany('StudentsReportCardsComments', ['className' => 'Institution.InstitutionStudentsReportCardsCommentsTable', 'foreignKey' => 'report_card_comment_code_id']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function getReportCardCommentCodesOptions()
    {
        return  $this
                ->find('list')
                ->find('visible')
                ->find('order')
                ->toArray();
    }
}
