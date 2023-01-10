<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Nationality'); ?></th>
                <th><?= __('Preferred'); ?></th>
            </thead>
            <tbody>
                <?php foreach($attr['data'] as $index) { ?>
                <tr>
                    <td><?=$index->nationalities_look_up->name;?></td>
                    <td>
                        <?php
                            if ($index->preferred) {
                                echo (__('Yes'));
                            } else {
                                echo (__('No'));
                            }
                        ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php endif ?>