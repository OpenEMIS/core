<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
echo $this->Html->script('institution_site_students', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="students_search" class="content_wrapper search">
    <h1>
		<span><?php echo __('List of Students'); ?></span>
		<?php
		if($_add_student) {
			echo $this->Html->link(__('Add'), array('action' => 'studentsAdd', $selectedYear), array('class' => 'divider'));
		}
		?>
	</h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('Student', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'students'),
		'inputDefaults' => array('label' => false, 'div' => false)
	)); 
	?>
    <div class="row">
        <div class="search_wrapper">
        	<?php 
				echo $this->Form->input('SearchField', array(
					'id' => 'SearchField',
					'value' => $searchField,
					'class' => 'default',
					'placeholder' => __('Student Identification No or Student Name')
				));
            ?>
            <span class="icon_clear">X</span>
        </div>
		<span class="left icon_search" onclick="$('form').submit()"></span>
    </div>
	
	<div class="row">
		<?php
		echo $this->Form->input('school_year', array(
			'id' => 'SchoolYearId',
			'class' => 'search_select',
			'empty' => __('All Years'),
			'options' => $yearOptions,
			'default' => $selectedYear
		));
		?>
	</div>
	
	<div class="row">
		<?php
		echo $this->Form->input('education_programme_id', array(
			'id' => 'EducationProgrammeId',
			'class' => 'search_select',
			'empty' => __('All Programmes'),
			'options' => $programmeOptions,
			'default' => $selectedYear
		));
		?>
	</div>
	<?php
	$orderSort = $order==='asc' ? 'up' : 'down';
	echo $this->Form->hidden('orderBy', array('value' => $orderBy));
	echo $this->Form->hidden('order', array('value' => $order));
	echo $this->Form->hidden('page', array('value' => $page));
	echo $this->Form->end();
	?>
    <div id="mainlist">
		<?php if(!empty($data)) { ?>
        <div class="row">
            <ul id="pagination">
                <?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
                <?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
                <?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
            </ul>
		</div>
		<?php } ?>

        <div class="table allow_hover" action="InstitutionSites/studentsView/">
            <div class="table_head">
				<div class="table_cell cell_id_no">
					<span class="left"><?php echo __('Identification No.'); ?></span>
					<span class="icon_sort_<?php echo ($orderBy =='Student.identification_no')?$orderSort:'up'; ?>" orderBy="Student.identification_no"></span>
                </div>
				<div class="table_cell cell_name">
					<span class="left"><?php echo __('First Name'); ?></span>
					<span class="icon_sort_<?php echo ($orderBy =='Student.first_name')?$orderSort:'up'; ?>" orderBy="Student.first_name"></span>
                </div>
				<div class="table_cell cell_name">
					<span class="left"><?php echo __('Last Name'); ?></span>
					<span class="icon_sort_<?php echo ($orderBy =='Student.last_name')?$orderSort:'up'; ?>" orderBy="Student.last_name"></span>
                </div>
				<div class="table_cell">
					<span class="left"><?php echo __('Programme'); ?></span>
					<span class="icon_sort_<?php echo ($orderBy =='EducationProgramme.name')?$orderSort:'up'; ?>" orderBy="EducationProgramme.name"></span>
                </div>
			</div>
            
            <div class="table_body">
				<?php foreach($data as $obj) { ?>
				<?php
				$idNo = $this->Utility->highlight($searchField, $obj['Student']['identification_no']);
				$firstName = $this->Utility->highlight($searchField, $obj['Student']['first_name']);
				$lastName = $this->Utility->highlight($searchField, $obj['Student']['last_name']);
				?>
                <div class="table_row" row-id="<?php echo $obj['Student']['id']; ?>">
					<div class="table_cell"><?php echo $idNo; ?></div>
					<div class="table_cell"><?php echo $firstName; ?></div>
					<div class="table_cell"><?php echo $lastName; ?></div>
					<div class="table_cell"><?php echo $obj['EducationProgramme']['name']; ?></div>
				</div>
				<?php } ?>
            </div>
        </div>
		
        <div class="row">
            <ul id="pagination">
                <?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
				<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
				<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
            </ul>
        </div>
    </div> <!-- mainlist end-->
</div>
