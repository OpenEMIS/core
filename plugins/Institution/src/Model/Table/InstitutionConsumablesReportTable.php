<?php

namespace Institution\Model\Table;


use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\I18n\Date;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;
use Cake\Collection\Collection;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;


class InstitutionConsumablesReportTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        $this->setTable('institution_consumables');
        parent::initialize($config);

        $this->belongsTo('StockUnits', ['className' => 'FieldOption.StockUnits', 'foreignKey' => 'stock_unit_id']);
        $this->belongsTo('ItemTypes', ['className' => 'FieldOption.ItemTypes', 'foreignKey' => 'item_type_id']);
        $this->hasMany('InstitutionConsumableTransactions', [
            'className' => 'Institution.InstitutionConsumableTransactions',
            'foreignKey' => 'institution_consumable_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

       
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Consumables' => ['id']]
        ]);
        $this->addBehavior('Excel',[
            'excludes' => ['security_user_id'],
            'pages' => [false],
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key'   => 'InstitutionConsumablesReport.bin_no',
            'field' => 'bin_no',
            'type'  => 'string',
            'label' => __('Bin No'),
        ];
        $newFields[] = [
            "key" => "InstitutionConsumablesReport.item_type_id",
            "field" => "item_type_id",
            "type" => "integer",
            "label" => "Item Type",
            "style" => [],
            "formatting" => "GENERAL",
        ];
        $newFields[] = [
            "key" => "InstitutionConsumablesReport.stock_unit_id",
            "field" => "stock_unit_id",
            "type" => "integer",
            "label" => "Stock Unit",
            "style" => [],
            "formatting" => "GENERAL",
        ];
        $newFields[] = [
            "key" => "InstitutionConsumablesReport.minimum",
            "field" => "minimum",
            "type" => "integer",
            "label" => "Minimum",
            "style" => [],
            "formatting" => "GENERAL",
        ];
        
        $newFields[] = [
            "key" => "InstitutionConsumablesReport.date",
            "field" => "date",
            "type" => "date",
            "label" => "Date",
            "style" => [],
            "formatting" => "GENERAL",
        ];
        $newFields[] = [
            "key" => "InstitutionConsumablesReport.received",
            "field" => "received",
            "type" => "integer",
            "label" => "Received",
            "style" => [],
            "formatting" => "GENERAL",
        ];
        $newFields[] = [
            "key" => "InstitutionConsumablesReport.issued",
            "field" => "issued",
            "type" => "integer",
            "label" => "Issued",
            "style" => [],
            "formatting" => "GENERAL",
        ];
        $newFields[] = [
            "key" => "InstitutionConsumablesReport.balance",
            "field" => "balance",
            "type" => "integer",
            "label" => "Balance",
            "style" => [],
            "formatting" => "GENERAL",
        ];
        $newFields[] = [
            'key' => 'InstitutionConsumablesReport',
            'field' => 'created_user_id',
            'type' => 'string',
            'label' => 'Created By',
        ];
        $newFields[] = [
            'key' => 'InstitutionConsumablesReport',
            'field' => 'created',
            'type' => 'date',
            'label' => 'Created On',
        ];

        $fields->exchangeArray($newFields);
    }   

    public function onExcelRenderDate(Event $event, Entity $entity, $attr)
    {
        $field = $entity->{$attr['field']};
        
        if (!empty($field)) {
            if ($field instanceof FrozenTime || $field instanceof FrozenDate) {
                return $this->formatDate($field);
            } else {
                $date = new FrozenTime($field);
                return $this->formatDate($date);
            }
        } else {
            return $field;
        }
        
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id;
        $query->where([$this->aliasField('institution_id') => $institutionId])->contain('InstitutionConsumableTransactions')
        ->enableHydration(true);
    
        $query->formatResults(function ($results) {
            $finalResults = [];
        
            foreach ($results as $row) {
                $transactions = $row['institution_consumable_transactions'];
                
                // If there are no child transactions, just keep the original row
                if (empty($transactions)) {
                    $finalResults[] = $row;
                    continue;
                }
        
                // Duplicate parent for each transaction
                foreach ($transactions as $transaction) {
                    $item = clone $row; // Use clone to avoid overwriting original entity
                    $item['institution_consumable_transactions'] = [$transaction];
        
                    // Add child data to parent level (optional)
                    $item['date'] = $transaction['date'];
                    $item['received'] = $transaction['received'];
                    $item['issued'] = $transaction['issued'];
                    $item['balance'] = $transaction['balance'];
        
                    $finalResults[] = $item;
                }
            }
        
            return new Collection($finalResults);
        });
        return $query;     
       
    }
}
