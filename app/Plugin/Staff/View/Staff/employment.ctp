<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('fieldset', 'stylesheet', array('inline' => false));
?>

<style>
.cell_year {
    width: 50px;
}

.cell_grade {
    width: 100px;
}

.table_row .table_cell{
    text-align: center;
}
</style>

<?php echo $this->element('breadcrumb'); ?>

<div id="additional" class="content_wrapper">
    <h1>
        <span><?php echo __('Employment'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>

    <?php
     if(isset($data) AND !empty($data) AND is_array($data)){
        foreach($data as $key => $classes){ ?>
    <fieldset class="section_group">
        <legend><?php echo $key; ?></legend>
        <div class="table">
            <div class="table_head">
                <div class="table_cell cell_position">Position</div>
                <div class="table_cell cell_from">From</div>
                <div class="table_cell cell_to">To</div>
                <div class="table_cell cell_grade">Salary</div>
            </div>
            <div class="table_body">
                <?php foreach($classes as $class){ ?>
                <div class="table_row">
                    <div class="table_cell"><?php echo $class['name']; ?></div>
                    <div class="table_cell"><?php echo $class['start_date']; ?></div>
                    <div class="table_cell"><?php echo (empty($class['end_date']))? 'Current':$class['end_date']; ?></div>
                    <div class="table_cell"><?php echo $class['salary']; ?></div>
                </div>
                <?php } ?>
            </div>
        </div>
    </fieldset>
    <?php }
    }else{ ?>
    <div class="alert alert_view alert_info">
        <div class="alert_icon"></div>
        <div class="alert_content"><?php echo __('No Employment found.'); ?></div>
    </div>
    <?php } ?>

</div>