<?php
    $alias = $ControllerAction['table']->alias();
?>
<div class="input">
    <!-- <label>Remote</label> -->
    <div class="input-form-wrapper">
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead>
                        <tr>
                            <!-- <th>Institution Affected</th> -->
                            <th><?= $this->Label->get('Areas.institution_affected');?></th>
                            <th><?= $this->Label->get('Areas.security_group_affected');?></th>
                            <th><?= $this->Label->get('Areas.missing_area');?></th>
                            <th><?= $this->Label->get('Areas.new_area');?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!is_null($associatedRecords)) : ?>
                            <?php foreach ($associatedRecords as $key => $value) : ?>
                                <?php
                                    $prefix = $alias.'.transfer_areas.'.$key;
                                ?>
                                <tr>
                                    <td><?= $value['institution']; ?></td>
                                    <td><?= $value['security_group']; ?></td>
                                    <td>
                                        <?php
                                            echo $value['name'];
                                            echo $this->Form->hidden("$prefix.area_id", ['value' => $value['id']]);
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                            $options = [
                                                'class' => 'form-control',
                                                'label' => false,
                                                'options' => $newAreaLists
                                            ];
                                            echo $this->Form->input("$prefix.new_area_id", $options);
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        <?php endif; ?>

                        <!-- <?php
                            if (!is_null($associatedRecords)) {
                                foreach ($associatedRecords as $key => $value) {
                                    echo '<tr>
                                        <td>'
                                             . htmlentities($value['institution']) .
                                        '</td>
                                        <td>'
                                             . htmlentities($value['security_group']) .
                                        '</td>
                                        <td>'
                                             . htmlentities($value['name']) .
                                        '</td>
                                        <td>'
                                             . htmlentities($value['name']) .
                                        '</td>
                                    <tr>'
                                    ;
                                }
                            } else {

                            }
                        ?> -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
