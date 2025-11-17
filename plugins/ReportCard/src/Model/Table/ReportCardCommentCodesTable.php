<?php
namespace ReportCard\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;

class ReportCardCommentCodesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->hasMany('StudentsReportCardsComments', ['className' => 'Institution.InstitutionStudentsReportCardsComments', 'foreignKey' => 'report_card_comment_code_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'ReportCardComments' => ['index']
        ]);
    }

    public function getReportCardCommentCodesOptions()
    {
        return  $this
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();
    }

    public function findCommentCodeOptionsData(Query $query, array $options)
    {
       return $query->select(['id', 'name'])
       ->find('visible')
       ->find('order');
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
