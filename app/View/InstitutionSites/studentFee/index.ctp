<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
$this->end();

$this->start('contentBody');
//echo $this->element('templates/year_options', array('url' => $_action));
?>
<div class="row page-controls">
    <?php
    echo $this->Form->input('school_year_id', array(
        'id' => 'SchoolYearId',
        'class' => 'search_select form-control',
        'label' => false,
        'options' => $yearOptions,
        'default' => $selectedYear,
        'div' => 'col-md-2',
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
        'div' => 'col-md-5',
        'url' => sprintf('%s/%s/%s', $this->params['controller'], $_action, $selectedYear),
        'onchange' => 'jsForm.change(this)'
    ));
    ?>
    <?php
    echo $this->Form->input('education_grade_id', array(
        'id' => 'EducationGradeId',
        'class' => 'search_select form-control',
        'label' => false,
        'empty' => __('All Grades'),
        'options' => $gradeOptions,
        'default' => $selectedGrade,
        'div' => 'col-md-5',
        'url' => sprintf('%s/%s/%s/%s', $this->params['controller'], $_action, $selectedYear, $selectedProgramme),
        'onchange' => 'jsForm.change(this)'
    ));
    ?>
</div>
<?php if(isset($programmes)) { 
    foreach($programmes as $programme){ ?>
    <fieldset class="section_group">
    <legend><?php echo $programme['education_programme_name']; ?></legend>
      <?php 
      if(isset($programme['education_grades'])){
      foreach($programme['education_grades'] as $key=>$val){ ?>
            <fieldset class="section_group">
            <legend><?php echo $val; ?></legend>
            <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <?php if(isset($data[$programme['id'].'_'.$key])){ ?>
                <thead url="<?php echo $this->params['controller'];?>/session/">
                    <tr>
                        <th>
                            <span class="left"><?php echo $this->Label->get('general.openemisId'); ?></span>
                        </th>
                        <th>
                            <span class="left"><?php echo $this->Label->get('general.name');?></span>
                        </th>
                        <th>
                            <span class="left"><?php echo $this->Label->get('FinanceFee.paid'); ?></span>
                        </th>
                          <th>
                            <span class="left"><?php echo $this->Label->get('FinanceFee.outstanding'); ?></span>
                        </th>
                    </tr>
               </thead>
                <tbody>
                	<?php 
                    foreach($data[$programme['id'].'_'.$key] as $id=>$val) {  ?>
                    <tr row-id="<?php echo $val['id']; ?>">
                        <td class="table_cell"><?php echo $val['identification_no']; ?></td>
                    	<td class="table_cell">
                            <?php 
                            echo $this->Html->link($val['name'], array('action' => 'studentFeeView', $val['student_id'], $val['id']), array('escape' => false));
                            ?>
                        </td>
                        <td class="table_cell" width="15%"><?php echo number_format($val['total_paid'],2); ?></td>
                        <td class="table_cell" width="15%"><?php echo number_format($val['total_outstanding'],2); ?></td>
                    </tr>
                   <?php 
                    } 
                  ?>
                </tbody>
                <?php }else{ ?>
                    <tbody><tr><td align="center"><?php echo $this->Label->get('FinanceFee.no_student'); ?></td></tr></tbody>
                <?php } ?>
            </table>
            </div>
        </fieldset>
        <?php 
            }
        }
        ?>
    </fieldset>
    <?php } ?>
<?php } ?>
<?php $this->end(); ?>  
