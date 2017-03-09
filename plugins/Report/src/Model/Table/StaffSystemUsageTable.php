<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffSystemUsageTable extends AppTable  {
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $selectedUsageType = !empty($requestData->system_usage) ? $requestData->system_usage : null;

        $query->where([$this->aliasField('is_staff') => 1]);

        if ($selectedUsageType == 1) {
            $query->where([$this->aliasField('last_login') . ' IS NULL']);

        } else if ($selectedUsageType == 2) {
            $lastSevenDays = new Date('-7 days');
            $formattedDate = $lastSevenDays->format('Y-m-d H:i:s');
            $query->where([$this->aliasField('last_login').' >= ' => $formattedDate]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'StaffSystemUsage.openemis_no',
            'field' => 'openemis_no',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffSystemUsage.username',
            'field' => 'username',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffSystemUsage.first_name',
            'field' => 'first_name',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffSystemUsage.middle_name',
            'field' => 'first_name',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffSystemUsage.third_name',
            'field' => 'first_name',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffSystemUsage.last_name',
            'field' => 'first_name',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffSystemUsage.last_login',
            'field' => 'last_login',
            'type' => 'integer',
            'label' => '',
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetLastLogin(Event $event, Entity $entity)
    {
        if ($entity->has('last_login')) {
            $lastlogin = $entity->last_login;
            return $lastlogin->nice();
        } else {
            return '';
        }
    }
}