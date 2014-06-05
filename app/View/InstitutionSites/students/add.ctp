<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->css('webkit_scrollbar', 'stylesheet', array('inline' => false));

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site_students', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Student'));

$this->start('contentBody');
?>

<div id="studentsAdd" class="content_wrapper edit">

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
					'onkeypress' => 'return InstitutionSiteStudents.search(this, event)'
				));
				?>
				<span class="icon_clear" onClick="$('#SearchField').val('')">X</span>
			</div>
			<span class="left icon_search" url="InstitutionSites/studentsSearch/" onClick="InstitutionSiteStudents.search(this)"></span>
		</div>

		<div class="table_scrollable searchTableWrapper">
			<table class="table table_header allow_hover table-striped table-hover table-bordered">
				<thead class="table_head">
					<tr>
						<td class="table_cell cell_id_no"><?php echo __('OpenEMIS ID'); ?></td>
						<td class="table_cell first_name"><?php echo __('First Name'); ?></td>
						<td class="table_cell middle_name"><?php echo __('Middle Name'); ?></td>
						<td class="table_cell"><?php echo __('Last Name'); ?></td>
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
	$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'InstitutionSites', 'action' => 'studentsSave'));
	$labelOptions = $formOptions['inputDefaults']['label'];
	echo $this->Form->create('InstitutionSiteStudent', $formOptions);
	echo $this->Form->hidden('student_id', array('id' => 'StudentId', 'value' => 0, 'autocomplete' => 'off'));
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
			$labelOptions['text'] = $this->Label->get('general.year');
			echo $this->Form->input('school_year_id', array(
				'class' => 'form-control',
				'options' => $yearOptions,
				'url' => 'InstitutionSites/programmesOptions',
				'onchange' => 'InstitutionSiteStudents.getProgrammeOptions(this)',
				'label' => $labelOptions
			));
			
			$labelOptions['text'] = $this->Label->get('InstitutionSite.programme');
			echo $this->Form->input('institution_site_programme_id', array('id' => 'InstitutionSiteProgrammeId', 'class' => 'form-control', 'options' => $programmeOptions, 'label' => $labelOptions));
			
			$labelOptions['text'] = $this->Label->get('general.status');
			echo $this->Form->input('student_status_id', array('class' => 'form-control', 'options' => $statusOptions, 'label' => $labelOptions));
			
			echo $this->FormUtility->datepicker('start_date', array('id' => 'startDate'));	
		?>
	</div>

	<div class="controls">
		<input type="submit" value="<?php echo __('Add'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'students'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>
