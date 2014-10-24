<?php /*
  $pageOptions = array('escape'=>false,'style' => 'display:none');
  $pageNumberOptions = array('modulus'=>5,'first' => 2, 'last' => 2,'tag' => 'li', 'separator'=>'','ellipsis'=>'<li><span class="ellipsis">...</span></li>');
  ?>
  <div class="row">
  <ul id="pagination">
  <?php echo $this->Paginator->prev(__('Previous') , null, null, $pageOptions); ?>
  <?php echo $this->Paginator->numbers($pageNumberOptions); ?>
  <?php echo $this->Paginator->next(__('Next') , null, null, $pageOptions); ?>
  </ul>
  <div style="clear:both"></div>
  </div>
  <div class="table allow_hover" action="Students/viewStudent/" total="<?php echo $this->Paginator->counter('{:count}'); ?>">
  <div class="table_head" url="Students/index">

  <div class="table_cell cell_id_no">
  <span class="left"><?php echo __('OpenEMIS ID'); ?></span>
  <span class="icon_sort_<?php echo ($sortedcol =='Student.identification_no')?$sorteddir:'up'; ?>"  order="Student.identification_no"></span>
  </div>
  <div class="table_cell cell_name">
  <span class="left"><?php echo __('First Name'); ?></span>
  <span class="icon_sort_<?php echo ($sortedcol =='Student.first_name')?$sorteddir:'up'; ?>" order="Student.first_name"></span>
  </div>
  <div class="table_cell cell_name">
  <span class="left"><?php echo __('Middle Name'); ?></span>
  <span class="icon_sort_<?php echo ($sortedcol =='Student.middle_name')?$sorteddir:'up'; ?>" order="Student.middle_name"></span>
  </div>
  <div class="table_cell cell_name">
  <span class="left"><?php echo __('Last Name'); ?></span>
  <span class="icon_sort_<?php echo ($sortedcol =='Student.last_name')?$sorteddir:'up'; ?>" order="Student.last_name"></span>
  </div>
  <div class="table_cell cell_gender">
  <span class="left"><?php echo __('Gender'); ?></span>
  <span class="icon_sort_<?php echo ($sortedcol =='Student.gender')?$sorteddir:'up'; ?>" order="Student.gender"></span>
  </div>
  <div class="table_cell cell_birthday">
  <span class="left"><?php echo __('Date of Birth'); ?></span>
  <span class="icon_sort_<?php echo ($sortedcol =='Student.date_of_birth')?$sorteddir:'up'; ?>" order="Student.date_of_birth"></span>
  </div>

  </div>

  <div class="table_body">
  <?php
  //pr($students);
  //pr($searchField);
  if(isset($students) && count($students) > 0){
  $ctr = 1;
  foreach ($students as $arrItems):
  $id = $arrItems['Student']['id'];
  $identificationNo = $this->Utility->highlight($searchField, $arrItems['Student']['identification_no']);
  $firstName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Student']['first_name'].'</b>'.((isset($arrItems['Student']['history_first_name']))?'<br>'.$arrItems['Student']['history_first_name']:''));
  $middleName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Student']['middle_name'].'</b>'.((isset($arrItems['Student']['history_middle_name']))?'<br>'.$arrItems['Student']['history_middle_name']:''));
  $lastName = $this->Utility->highlight($searchField, '<b>'.$arrItems['Student']['last_name'].'</b>'.((isset($arrItems['Student']['history_last_name']))?'<br>'.$arrItems['Student']['history_last_name']:''));
  $gender = $arrItems['Student']['gender'];
  $birthday = $arrItems['Student']['date_of_birth'];
  ?>
  <div row-id="<?php echo $id ?>" class="table_row table_row_selection <?php echo ((($ctr++%2) != 0)?'odd':'even');?>">
  <div class="table_cell"><?php echo $identificationNo; ?></div>
  <div class="table_cell"><?php echo $firstName; ?></div>
  <div class="table_cell"><?php echo $middleName; ?></div>
  <div class="table_cell"><?php echo $lastName; ?></div>
  <div class="table_cell"><?php echo $gender; ?></div>
  <div class="table_cell"><?php echo $this->Utility->formatDate($birthday); ?></div>
  </div>
  <?php endforeach;
  }
  ?>
  </div>
  </div>

  <?php if(sizeof($students)==0) { ?>
  <div class="row center" style="color: red; margin-top: 15px;"><?php echo __('No Student found.'); ?></div>
  <?php } ?>
  <div class="row">
  <ul id="pagination">
  <?php echo $this->Paginator->prev(__('Previous') , null, null, $pageOptions); ?>
  <?php echo $this->Paginator->numbers($pageNumberOptions); ?>
  <?php echo $this->Paginator->next(__('Next') , null, null, $pageOptions); ?>
  </ul>
  </div>
 */ ?>

<?php
$pageOptions = array('escape' => false, 'style' => 'display:none');
$pageNumberOptions = array('modulus' => 5, 'first' => 2, 'last' => 2, 'tag' => 'li', 'separator' => '', 'ellipsis' => '<li><span class="ellipsis">...</span></li>');
?>
<div class="row">
    <ul id="pagination">
		<?php echo $this->Paginator->prev(__('Previous'), null, null, $pageOptions); ?>
		<?php echo $this->Paginator->numbers($pageNumberOptions); ?>
<?php echo $this->Paginator->next(__('Next'), null, null, $pageOptions); ?>
    </ul>
</div>

<table class="table table-striped table-hover table-bordered" total="<?php echo $this->Paginator->counter('{:count}'); ?>">
    <thead>
		<tr>
			<td class="cell_code">
                <span class="left"><?php echo __('OpenEMIS ID'); ?></span>
                <span class="icon_sort_<?php echo ($sortedcol == 'Student.identification_no') ? $sorteddir : 'up'; ?>"  order="Student.identification_no"></span>
				</div>
			<td class="cell_code">
				<span class="left"><?php echo __('Name'); ?></span>
				<span class="icon_sort_<?php echo ($sortedcol == 'Student.first_name') ? $sorteddir : 'up'; ?>" order="Student.first_name"></span>
			</td>
			<?php /* <td class="table_cell cell_code">
			  <span class="left"><?php echo __('Middle Name'); ?></span>
			  <span class="icon_sort_<?php echo ($sortedcol =='Student.middle_name')?$sorteddir:'up'; ?>" order="Student.middle_name"></span>
			  </td>
			  <td class="table_cell cell_code">
			  <span class="left"><?php echo __('Last Name'); ?></span>
			  <span class="icon_sort_<?php echo ($sortedcol =='Student.last_name')?$sorteddir:'up'; ?>" order="Student.last_name"></span>
			  </td> */ ?>
			<td class="cell_code">
				<span class="left"><?php echo __('Gender'); ?></span>
				<span class="icon_sort_<?php echo ($sortedcol == 'Student.gender') ? $sorteddir : 'up'; ?>" order="Student.gender"></span>
			</td>
			<td class="cell_code">
				<span class="left"><?php echo __('Date of Birth'); ?></span>
				<span class="icon_sort_<?php echo ($sortedcol == 'Student.date_of_birth') ? $sorteddir : 'up'; ?>" order="Student.date_of_birth"></span>
			</td>
		</tr> 
    </thead>

    <tbody>
		<?php
		//pr($student);
		if (isset($students) && count($students) > 0) {
			$ctr = 1;
			foreach ($students as $arrItems):
				$id = $arrItems['Student']['id'];
				$identificationNo = $this->Utility->highlight($searchField, $arrItems['Student']['identification_no']);
				$firstName = $this->Utility->highlight($searchField, $arrItems['Student']['first_name'] . ((isset($arrItems['Student']['history_first_name'])) ? '<br>' . $arrItems['Student']['history_first_name'] : ''));
				$middleName = $this->Utility->highlight($searchField, $arrItems['Student']['middle_name'] . ((isset($arrItems['Student']['history_middle_name'])) ? '<br>' . $arrItems['Student']['history_middle_name'] : ''));
				$lastName = $this->Utility->highlight($searchField, $arrItems['Student']['last_name'] . ((isset($arrItems['Student']['history_last_name'])) ? '<br>' . $arrItems['Student']['history_last_name'] : ''));
				$gender = $arrItems['Student']['gender'];
				$birthday = $arrItems['Student']['date_of_birth'];
				?>
				<tr class="table_row_selection <?php echo ((($ctr++ % 2) != 0) ? 'odd' : 'even'); ?>">
					<td><?php echo $identificationNo; ?></td>
					<td><?php echo $this->Html->link($firstName . ' ' . $lastName, array('action' => 'view', $id), array('escape' => false)); ?></td>
					<?php /* <td class="table_cell"><?php echo $middleName; ?></td>
					  <td class="table_cell"><?php echo $lastName; ?></td> */ ?>
					<td><?php echo $gender; ?></td>
					<td><?php echo $this->Utility->formatDate($birthday); ?></td>
				</tr>
			<?php
			endforeach;
		}
		?>
    </tbody>
</table>

<?php if (sizeof($students) == 0) { ?>
	<div class="row center" style="color: red; margin-top: 15px;"><?php echo __('No Student found.'); ?></div>
<?php } ?>
<div class="row">
    <ul id="pagination">
		<?php echo $this->Paginator->prev(__('Previous'), null, null, $pageOptions); ?>
		<?php echo $this->Paginator->numbers($pageNumberOptions); ?>
<?php echo $this->Paginator->next(__('Next'), null, null, $pageOptions); ?>
    </ul>
</div>
