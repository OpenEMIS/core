<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class UpdateSecurityGroupUsersShell extends Shell
{
    private $homeroomRoleId;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Security.SecurityRoles');
        $this->loadModel('Security.SecurityGroupUsers');
        $this->loadModel('Institution.StaffPositionTitles');
        $this->loadModel('Institution.InstitutionPositions');
        $this->loadModel('Institution.Staff');
    }
    
    public function main()
    {
        $this->out('------- Start SecurityGroupUsersShell -------');

        try {
            $this->extractSecurityRoleIds();
            if (!is_null($this->homeroomRoleId)) {
                $this->patchFromInstitutionStaff();
            } else {
                $this->out('No role id found. Please configure your security_roles table before running patch.');
            }
        } catch (Exception $e) {
            $this->out($e->getMessage());
        }

        $this->out('------- End SecurityGroupUsersShell -------');
    }

    private function extractSecurityRoleIds()
    {
        $this->out('Extracting homeroom role and teacher role id');

        $homeroomRoleId = $this->SecurityRoles
            ->find()
            ->select([$this->SecurityRoles->aliasField('id')])
            ->where([$this->SecurityRoles->aliasField('code') => 'HOMEROOM_TEACHER'])
            ->first();

        if (!is_null($homeroomRoleId)) {
            $this->homeroomRoleId = $homeroomRoleId->id;
        }
    }

    private function patchFromInstitutionStaff()
    {
        $this->out('SecurityGroupUsersShell - patchFromInstitutionStaff - Patching table based on institution_staff by homeroom teacher role');

        $this->out('Finding all records in institution_staff that has empty security_group_user_id and is currently active');
        $emptyRecords = $this->Staff
            ->find()
            ->select([
                'id' => $this->Staff->aliasField('id'),
                'security_group_id' => 'Institutions.security_group_id',
                'security_user_id' => $this->Staff->aliasField('staff_id'),
                'security_role_id' => 'StaffPositionTitles.security_role_id',
                'is_homeroom' => 'Positions.is_homeroom'
            ])
            ->contain(['Institutions', 'Positions.StaffPositionTitles'])
            ->where([
                $this->Staff->aliasField('security_group_user_id IS NULL'),
                $this->Staff->aliasField('staff_status_id') => 1
            ])
            ->toArray();

        if (!empty($emptyRecords)) {
            $this->out('Staff records found. Start patching');
            foreach ($emptyRecords as $record) {
                $uuid = Text::uuid();
                $securityGroupId = $record->security_group_id;
                $securityUserId = $record->security_user_id;
                $securityRoleId = $record->security_role_id;
                $date = date('Y-m-d H:i:s');

                $data = [
                    'id' => $uuid,
                    'security_group_id' => $securityGroupId,
                    'security_user_id' => $securityUserId,
                    'security_role_id' => $securityRoleId,
                    'created' => $date,
                    'created_user_id' => 1
                ];

                $this->out('Inserting record for staff: ' . $securityUserId . ' with role: ' . $securityRoleId . ' to security_group_users');
                $this->SecurityGroupUsers
                    ->query()
                    ->insert(['id', 'security_group_id', 'security_user_id', 'security_role_id', 'created', 'created_user_id'])
                    ->values($data)
                    ->execute();

                if ($record->is_homeroom) {
                    $this->out('Record is homeroom, insert homeroom role record to security_group_users');
                    $homeroomData = [
                        'id' => Text::uuid(),
                        'security_group_id' => $securityGroupId,
                        'security_user_id' => $securityUserId,
                        'security_role_id' => $this->homeroomRoleId,
                        'created' => $date,
                        'created_user_id' => 1
                    ];

                    $this->SecurityGroupUsers
                        ->query()
                        ->insert(['id', 'security_group_id', 'security_user_id', 'security_role_id', 'created', 'created_user_id'])
                        ->values($homeroomData)
                        ->execute();
                }

                $this->out('Update foreign key for staff record in institution_staff with the new uuid generated');
                $this->Staff->updateAll(
                    ['security_group_user_id' => $uuid],
                    ['id' => $record->id]
                );

                $this->out('------- Start of UpdateAssigneeShell -------');
                $this->out('- UpdateAssigneeShell for staff: ' . $securityUserId);
                $result = $this->dispatchShell([
                    'command' => ['UpdateAssignee', '0', '0', '0', '0', $securityUserId, '']
                ]);
                $this->out('- UpdateAssigneeShell completes with result: ' . $result);
                $this->out('------- End of UpdateAssigneeShell -------');
            }
        } else {
            $this->out('No affected staff records found');
        }
    }
}
