<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="identity" class="content_wrapper">
    <h1>
        <span><?php echo __('Guardians'); ?></span>
        <?php
        if ($_add) {
            echo $this->Html->link(__('Add'), array('action' => 'guardiansAdd'), array('class' => 'divider'));
        }
        ?>
    </h1>

    <?php echo $this->element('alert'); ?>

    <div class="table allow_hover full_width" action="Students/guardiansView/">
        <div class="table_head">
            <div class="table_cell"><?php echo __('First Name'); ?></div>
            <div class="table_cell"><?php echo __('Last Name'); ?></div>
            <div class="table_cell"><?php echo __('Relationship'); ?></div>
            <div class="table_cell"><?php echo __('Mobile Phone'); ?></div>
        </div>

        <div class="table_body">
            <?php foreach ($list as $obj): ?>
                <div class="table_row" row-id="<?php echo $obj['Guardian']['id']; ?>">
                    <div class="table_cell"><?php echo $obj['Guardian']['first_name']; ?></div>
                    <div class="table_cell"><?php echo $obj['Guardian']['last_name']; ?></div>
                    <div class="table_cell"><?php echo $obj['GuardianRelation']['name']; ?></div>
                    <div class="table_cell"><?php echo $obj['Guardian']['mobile_phone']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>