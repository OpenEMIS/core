<?php if ($ControllerAction['action'] == 'view') : ?>
   
    <div class="table-in-view">
        <table class="table">
            <thead>
                <th><?= __('Identity Type'); ?></th>
                <th><?= __('Identity Number'); ?></th>
                <th><?= __('Nationality'); ?></th>
                <th><?= __('Preferred'); ?></th>
            </thead>
            <tbody>
            <?php
                 $nationality=[];
                  foreach($attr['data']['nationalities'] as $nat){
                     $preferred=$nat->preferred==0?'No':'Yes';
                     $nationality[$nat->nationality_id]=$preferred;
                  }
                 foreach($attr['data']['identities'] as $index) { ?>
                <tr>
                <td><?=$index->identity_type->name;?></td>
                <td><?=$index->number;?></td>
                <td><?=$index->nationality->name;?></td>
                <td><?=$nationality[$index->nationality_id];?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php endif ?>