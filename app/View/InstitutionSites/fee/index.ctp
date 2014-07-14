<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
$this->end();

$this->start('contentBody');
?>
<div class="row page-controls">
    <?php
    echo $this->Form->input('school_year_id', array(
        'id' => 'SchoolYearId',
        'class' => 'search_select form-control',
        'label' => false,
        'options' => $yearOptions,
        'default' => $selectedYear,
        'div' => 'col-md-3',
         'url' => sprintf('%s/%s', $this->params['controller'], $_action),
        'onchange' => 'jsForm.change(this)'

    ));
    ?>
    <?php
    echo $this->Form->input('education_programme_id', array(
        'id' => 'EducationProgrammeId',
        'class' => 'search_select form-control',
        'label' => false,
        'empty' => __('All Programmes'),
        'options' => $programmeOptions,
        'default' => $selectedProgramme,
        'div' => 'col-md-6',
        'url' => sprintf('%s/%s/%s', $this->params['controller'], $_action, $selectedYear),
        'onchange' => 'jsForm.change(this)'
    ));
    ?>
</div>

<?php if(isset($programmes)) { ?>
    <?php foreach($programmes as $programme){ ?>
    <fieldset class="section_group">
    <legend><?php echo (isset($programme['education_programme_name']) ? $programme['education_programme_name']: $programme['name']); ?></legend>
    <div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead url="<?php echo $this->params['controller'];?>/session/">
            <tr>
                <th>
                    <span class="left"><?php echo __('Grade'); ?></span>
                </th>
                <th>
                    <span class="left"><?php echo sprintf('%s (%s)',__('Fees'), $currency); ?></span>
                </th>
            </tr>
       </thead>
        <tbody>
        	<?php 
            if(!empty($data)){ 
            foreach($data[(isset($programme['education_programme_id']) ? $programme['education_programme_id'] : $programme['id'])] as $id=>$val) {  ?>
            <tr row-id="<?php echo $val['id']; ?>">
            	<td class="table_cell">
                    <?php 
                    if(isset($val['id'])){
                        echo $this->Html->link($val['grade'], array('action' => 'feeView', $val['id']), array('escape' => false));
                    }else{
                        if($_add) {
                            echo $this->Html->link($val['grade'], array('action' => 'feeAdd', $val['education_grade_id']), array('escape' => false));
                        }else{
                            $val['grade'];
                        }
                    }
                    ?>
                </td>
                <td class="table_cell"><?php echo $val['total_fee']; ?></td>
            </tr>
           <?php } 
            }
           ?>
        </tbody>
    </table>
    </div>
    </fieldset>
    <?php } ?>
<?php } ?>
<?php $this->end(); ?>  
