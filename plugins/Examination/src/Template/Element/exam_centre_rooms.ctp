<?php
    $alias = $ControllerAction['table']->alias();
    if ($ControllerAction['action'] == 'edit') {
        $this->Form->unlockField('ExaminationCentres.examination_centre_rooms');
    }
?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php if (isset($data['examination_centre_rooms']) && !empty($data['examination_centre_rooms'])) : ?>
        <div class="table-in-view">
            <table class="table">
                <thead>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Size') ?></th>
                    <th><?= __('Number of Seats') ?></th>
                </thead>
                <tbody>
                    <?php foreach ($data['examination_centre_rooms'] as $i => $item) :?>
                        <tr>
                            <td><?= $item->name ?></td>
                            <td><?= $item->size ?></td>
                            <td><?= $item->number_of_seats ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <?= __('No rooms') ?>
    <?php endif ?>

<?php elseif ($ControllerAction['action'] == 'edit') : ?>
    <?php
        echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add New Room').'</span>', [
            'label' => __('Add Room'),
            'type' => 'button',
            'class' => 'btn btn-default',
            'aria-expanded' => 'true',
            'onclick' => "$('#reload').val('addExamCentreRoom').click();"
        ]);
    ?>

    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table table-curved">
                <thead>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Size') ?></th>
                    <th><?= __('Number of Seats') ?></th>
                    <th></th>
                </thead>
                <?php if (isset($data['examination_centre_rooms'])) : ?>
                    <tbody>
                        <?php foreach ($data['examination_centre_rooms'] as $i => $item) : ?>
                             <?php
                                $fieldPrefix = "$alias.examination_centre_rooms.$i";
                            ?>
                            <tr>
                                <td>
                                    <?php
                                        echo $this->Form->hidden("$fieldPrefix.id");
                                        echo $this->Form->hidden("$fieldPrefix.examination_centre_id", ['value' => $data->id]);

                                        echo $this->Form->input("$fieldPrefix.name", [
                                            'type' => 'string',
                                            'label' => false
                                        ]);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        echo $this->Form->input("$fieldPrefix.size", [
                                            'type' => 'integer',
                                            'label' => false
                                        ]);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        echo $this->Form->input("$fieldPrefix.number_of_seats", [
                                            'type' => 'integer',
                                            'label' => false
                                        ]);
                                    ?>
                                </td>
                                <td>
                                    <?php
                                        echo "<button onclick='jsTable.doRemove(this);' aria-expanded='true' type='button' class='btn btn-dropdown action-toggle btn-single-action'><i class='fa fa-trash'></i>&nbsp;<span>Delete</span></button>";
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                <?php endif ?>
            </table>
        </div>
    </div>
<?php endif ?>
