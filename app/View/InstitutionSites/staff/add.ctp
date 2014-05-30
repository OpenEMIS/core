<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site_staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staffAdd" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Add Staff'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<fieldset class="section_group" id="search">
		<legend><?php echo __('Search'); ?></legend>
		
		<div class="row">
			<div class="search_wrapper">
				<?php 
					echo $this->Form->input('SearchField', array(
						'id' => 'SearchField',
						'label' => false,
						'div' => false,
						'class' => 'default',
						'placeholder' => __('OpenEMIS ID, First Name or Last Name'),
						'onkeypress' => 'return InstitutionSiteStaff.search(this, event)'
					));
				?>
				<span class="icon_clear" onClick="$('#SearchField').val('')">X</span>
			</div>
			<span class="left icon_search" url="InstitutionSites/staffSearch/" onClick="InstitutionSiteStaff.search(this)"></span>
		</div>
		
		<div class="table_scrollable">
			<div class="table table_header">
				<div class="table_head">
					<div class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></div>
					<div class="table_cell"><?php echo __('First Name'); ?></div>
                                        <div class="table_cell"><?php echo __('Middle Name'); ?></div>
					<div class="table_cell"><?php echo __('Last Name'); ?></div>
				</div>
			</div>
			<div class="list_wrapper hidden" limit="4" style="height: 98px;">
				<div class="table allow_hover">
					<div class="table_body"></div>
				</div>
			</div>
		</div>
	</fieldset>
	
	<?php
	echo $this->Form->create('InstitutionSiteStaff', array(
		'id' => 'submitForm',
		'onsubmit' => 'return InstitutionSiteStaff.validateStaffAdd()',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'staffSave')
	));
	echo $this->Form->hidden('staff_id', array('id' => 'StaffId', 'autocomplete' => 'off'));
	?>
	
	<div class="info">
		<div class="row">
			<div class="label"><?php echo __('OpenEMIS ID'); ?></div>
			<div class="value" id="IdentificationNo"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value" id="FirstName"></div>
		</div>
            
                <div class="row">
			<div class="label"><?php echo __('Middle Name'); ?></div>
			<div class="value" id="MiddleName"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value" id="LastName"></div>
		</div>
            
                <div class="row">
			<div class="label"><?php echo __('Preferred Name'); ?></div>
			<div class="value" id="PreferredName"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value" id="Gender"></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Position Number'); ?></div>
			<div class="value"><?php echo $this->Form->input('position_no', array('class' => 'default', 'maxlength' => 15)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Position Type'); ?></div>
			<div class="value"><?php echo $this->Form->input('staff_category_id', array('class' => 'default', 'options' => $categoryOptions)); ?></div>
		</div>
            
                <div class="row">
			<div class="label"><?php echo __('Position Title'); ?></div>
			<div class="value"><?php echo $this->Form->input('staff_position_title_id', array('class' => 'default', 'options' => $positionTitleptions)); ?></div>
		</div>
            
                <div class="row">
			<div class="label"><?php echo __('Position Grade'); ?></div>
			<div class="value"><?php echo $this->Form->input('staff_position_grade_id', array('class' => 'default', 'options' => $positionGradeOptions)); ?></div>
		</div>
            
                <div class="row">
			<div class="label"><?php echo __('Position Step'); ?></div>
			<div class="value"><?php echo $this->Form->input('staff_position_step_id', array('class' => 'default', 'options' => $positionStepOptions)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Form->input('staff_status_id', array('class' => 'default', 'options' => $statusOptions)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Start Date'); ?></div>
			<div class="value"><?php echo $this->Form->input('start_date', array('type' => 'date', 'dateFormat' => 'DMY', 'minYear' => $minYear, 'maxYear' => $maxYear, 'empty' => __('Select'))); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('FTE'); ?></div>
			<div class="value"><?php echo $this->Form->input('FTE', array('class' => 'default', 'onkeypress' => 'return utility.integerCheck(event)', 'maxlength' => 3)); ?></div>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Add'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'staff'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div> */?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

echo $this->Html->script('institution_site_staff', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Staff'));

$this->start('contentBody');
?>
<fieldset class="section_group" id="search">
	<legend><?php echo __('Search'); ?></legend>

	<div class="row">
		<div class="search_wrapper">
			<?php
			echo $this->Form->input('SearchField', array(
				'id' => 'SearchField',
				'label' => false,
				'div' => false,
				'class' => 'default',
				'placeholder' => __('OpenEMIS ID, First Name or Last Name'),
				'onkeypress' => 'return InstitutionSiteStaff.search(this, event)'
			));
			?>
			<span class="icon_clear" onClick="$('#SearchField').val('')">X</span>
		</div>
		<span class="left icon_search" url="InstitutionSites/staffSearch/" onClick="InstitutionSiteStaff.search(this)"></span>
	</div>

	<div class="table_scrollable searchTableWrapper">
		<table class="table table_header allow_hover table-striped table-hover table-bordered">
			<thead class="table_head">
				<tr>
					<td class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></td>
					<td class="table_cell"><?php echo __('Name'); ?></td>
				</tr>
			</thead>
		</table>
		<div class="list_wrapper hidden searchTableWrapper" limit="3" style="height: 98px;">
			<table class="table allow_hover table-striped table-hover table-bordered">
				<tbody class="table_body"></tbody>
			</table>
		</div>
	</div>
</fieldset>

<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'InstitutionSites', 'action' => 'staffSave'));
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['id'] = 'staff_add';
echo $this->Form->create('InstitutionSiteStaff', $formOptions);
echo $this->Form->hidden('staff_id', array('id' => 'StaffId', 'autocomplete' => 'off'));
?>
<div class="info dataDisplay">
	<div class="form-group">
		<label class="control-label col-md-3"><?php echo __('OpenEMIS ID'); ?></label>
		<div class="col-md-4" id="IdentificationNo"></div>
	</div>

	<div class="form-group">
		<label class="control-label col-md-3"><?php echo __('First Name'); ?></label>
		<div class="col-md-4" id="FirstName"></div>
	</div>

	<div class="form-group">
		<label class="control-label col-md-3"><?php echo __('Middle Name'); ?></label>
		<div class="col-md-4" id="MiddleName"></div>
	</div>

	<div class="form-group">
		<label class="control-label col-md-3"><?php echo __('Last Name'); ?></label>
		<div class="col-md-4" id="LastName"></div>
	</div>

	<div class="form-group">
		<label class="control-label col-md-3"><?php echo __('Preferred Name'); ?></label>
		<div class="col-md-4" id="PreferredName"></div>
	</div>

	<div class="form-group">
		<label class="control-label col-md-3"><?php echo __('Gender'); ?></label>
		<div class="col-md-4" id="Gender"></div>
	</div>

	<?php
	echo $this->Form->input('institution_site_position_id', array(
		'options' => $positionOptions,
		'default' => $selectedPositionId,
		'id' => 'institutionSitePositionId',
		'url' => 'InstitutionSites/staffAjaxRetriveUpdatedFTE',
		'onchange' => 'InstitutionSiteStaff.onPositioninfoChange(this)',
		'label' => array('text' => 'Position', 'class' => $labelOptions)
	));
	echo $this->Form->input('staff_status_id', array('label' => array('text' => $this->Label->get('general.status'), 'class' => $labelOptions), 'options' => $statusOptions));
	echo $this->FormUtility->datepicker('start_date', array('id' => 'startDate', 'onchange' => 'InstitutionSiteStaff.onPositioninfoChange(this)', 'url' => 'InstitutionSites/staffAjaxRetriveUpdatedFTE'));
	echo $this->Form->input('FTE', array('id' => 'fte', 'label' => array('text' => 'FTE', 'class' => $labelOptions), 'options' => $FTEOtpions));
	echo $this->Form->input('staff_type_id', array('label' => array('text' => $this->Label->get('general.type'), 'class' => $labelOptions), 'options' => $staffTypeOptions, 'default' => $staffTypeDefault));
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'staff')));
	?>
</div>
<?php
echo $this->Form->end();
$this->end();
?>