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
        <span><?php echo __('Classes'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>

    <?php
     if(isset($data) AND !empty($data) AND is_array($data)){
        foreach($data as $key => $classes){ ?>
    <fieldset class="section_group">
        <legend><?php echo $key; ?></legend>
        <div class="table">
            <div class="table_head">
                <div class="table_cell cell_year">Years</div>
                <div class="table_cell cell_class">Classes</div>
                <div class="table_cell cell_programme">Programmes</div>
                <div class="table_cell cell_grade">Grades</div>
            </div>
            <div class="table_body">
                <?php foreach($classes as $class){ ?>
                <div class="table_row">
                    <div class="table_cell"><?php echo $class['school_year']; ?></div>
                    <div class="table_cell"><?php echo $class['institution_site_class']; ?></div>
                    <div class="table_cell"><?php echo $class['education_programme']; ?></div>
                    <div class="table_cell"><?php echo $class['education_grade']; ?></div>
                </div>
                <?php } ?>
            </div>
        </div>
    </fieldset>
    <?php }
    }else{ ?>
    <div class="alert alert_view alert_info">
        <div class="alert_icon"></div>
        <div class="alert_content"><?php echo __('No Classes found.'); ?></div>
    </div>
    <?php } ?>

</div>