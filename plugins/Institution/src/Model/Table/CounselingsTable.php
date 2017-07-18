<?php
namespace Institution\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class CounselingsTable extends AppTable
{
    const ASSIGNED = 1;

    public function initialize(array $config)
    {
        $this->table('institution_counselors');
        parent::initialize($config);

        $this->belongsTo('GuidanceTypes', ['className' => 'Staff.GuidanceTypes', 'foreign_key' => 'guidance_type_id']);
        $this->belongsTo('Counselors', ['className' => 'Security.Users', 'foreign_key' => 'counselor_id']);
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator->allowEmpty('file_content');
    }

    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    public function getCounselorOptions($institutionId)
    {
        // get the staff that assigned from the institution from security user
        $InstitutionStaff = TableRegistry::get('Institution.Staff');

        $counselorOptions = $this->Counselors
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->innerJoin(
                    [$InstitutionStaff->alias() => $InstitutionStaff->table()],
                    [
                        $InstitutionStaff->aliasField('staff_id = ') . $this->Counselors->aliasField('id'),
                        $InstitutionStaff->aliasField('institution_id') => $institutionId,
                        $InstitutionStaff->aliasField('staff_status_id') => self::ASSIGNED
                    ]
                )
            ->order([
                $this->Counselors->aliasField('first_name'),
                $this->Counselors->aliasField('last_name')
            ])
            ->toArray();

        return $counselorOptions;
    }
}
