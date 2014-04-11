<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="shifts" class="content_wrapper">
    <h1>
        <span><?php echo __('Shifts'); ?></span>
        <?php
        if ($_add) {
            echo $this->Html->link(__('Add'), array('action' => 'shiftsAdd'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php echo $this->element('alert'); ?>

    <div class="table allow_hover full_width" action="InstitutionSites/shiftsView/">
        <div class="table_head">
            <div class="table_cell"><?php echo __('Year'); ?></div>
            <div class="table_cell"><?php echo __('Shift'); ?></div>
            <div class="table_cell"><?php echo __('Period'); ?></div>
            <div class="table_cell"><?php echo __('Location'); ?></div>
        </div>

        <div class="table_body">
            <?php foreach ($data as $obj): ?>
                <div class="table_row" row-id="<?php echo $obj['InstitutionSiteShift']['id']; ?>">
                    <div class="table_cell"><?php echo $obj['SchoolYear']['name']; ?></div>
                    <div class="table_cell"><?php echo $obj['InstitutionSiteShift']['name']; ?></div>
                    <div class="table_cell"><?php echo $obj['InstitutionSiteShift']['start_time']; ?> - <?php echo $obj['InstitutionSiteShift']['end_time']; ?></div>
                    <div class="table_cell"><?php echo $obj['InstitutionSite']['name']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>