
<h3><?= __('Department Staff') ?></h3>



<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table table-curved table-checkable table-input">
        <thead>
            <tr>
                <th><?= $this->Label->get('General.openemis_no') ?></th>
                <th><?= $this->Label->get('Users.name') ?></th>
                <th><?= $this->Label->get('Users.gender_id') ?></th>
                <th><?= __('Staff Status') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($attr['data']['staff'] as $obj):
                // normalize our data

                $openemis_no  = $obj['openemis_no'];
                $user_name   = $obj['name'];
                $status_name = $obj['staff_status_name'];
                $gender_name = $obj['gender_name'];
                $staff_id = $obj['staff_id'];
                $security_user_id = $obj['security_user_id'];
                $institution_id = $obj['institution_id'];
                ?>
                <tr>
                    <td>
                        <?php
                        $url = [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StaffUser',
                            'view',
                            $this->ControllerAction->paramsEncode(['id' => $staff_id,
                                'institution_id' => $institution_id,
                                'staff_id' => $security_user_id])
                        ];

                        ?>
                        <?= $this->html->link($openemis_no, $url) ?>

                    </td>
                    <td><?= h($user_name) ?></td>
                    <td><?= h($gender_name) ?></td>
                    <td><?= h($status_name) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
