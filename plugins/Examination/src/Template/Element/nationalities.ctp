<style>
.vertical-align-top {
    vertical-align: top !important;
}
</style>

<div class="form-input table-full-width">
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('Nationality'); ?></th>
                        <th><?= __('Preferred'); ?></th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php foreach($attr['data'] as $index) { ?>
                    <tr>
                        <td class="vertical-align-top"><?= $index->nationalities_look_up->name; ?></td>
                        <td class="vertical-align-top">
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
    </div>
</div>