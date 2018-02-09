<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add') : ?>
	<div class="input clearfix required">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table table-checkable">
					<thead>
						<tr>
							<th class="checkbox-column"><input type="checkbox" class="no-selection-label" kd-checkbox-radio/></th>
							<th><?= __('OpenEMIS ID') ?></th>
							<th><?= __('Student') ?></th>
							<th><?= __('Status') ?></th>
							<th><?= __('Class') ?></th>
							<th><?= __('Gender') ?></th>
						</tr>
					</thead>
					<?php if (isset($attr['data'])) : ?>
						<tbody>
							<?php
                            if (isset($attr['nextInstitutionGenderCode'])) {
                                    $nextInstitutionGenderCode = $attr['nextInstitutionGenderCode'];
                                }
                                foreach ($attr['data'] as $i => $obj) :
                                    $studentGender = $obj->_matchingData['Genders']->code;
							?>
								<tr>
									<?php
										$alias = $ControllerAction['table']->alias();
										$fieldPrefix = "$alias.students.$i";

										$checkboxOptions = ['class' => 'no-selection-label', 'kd-checkbox-radio' => ''];

                                        if (isset($nextInstitutionGenderCode) && $nextInstitutionGenderCode != 'X' && $nextInstitutionGenderCode != $studentGender) {
                                            $checkboxOptions['disabled'] = true;
                                            $tdClass = '';
                                            $tooltipMessage = __(sprintf('The selected institution only accepts %s student.', $attr['nextInstitutionGender']));
                                            $tooltip = '<i class="fa fa-exclamation-circle fa-lg table-tooltip icon-red" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $tooltipMessage . '"</i>';
                                        } else {
                                            $tdClass = 'checkbox-column';
                                            $tooltip = '';
                                        }
                                    ?>
                                    <td class=<?=$tdClass;?>>
                                    <?php
                                            echo $this->Form->checkbox("$fieldPrefix.selected", $checkboxOptions);
                                            echo $this->Form->hidden("$fieldPrefix.student_id", ['value' => $obj->student_id]);
                                    ?>
									</td>
									<td><?= $obj->_matchingData['Users']->openemis_no ?></td>
									<td><?= $obj->_matchingData['Users']->name ?></td>
									<td><?= $attr['attr']['statusOptions'][$obj->student_status_id ]?></td>
									<td><?= isset($attr['classOptions'][$obj->institution_class_id]) ? $attr['classOptions'][$obj->institution_class_id] : '' ?></td>
									<td><?= $obj->_matchingData['Genders']->name ?> <?=$tooltip;?></td>
								</tr>
							<?php endforeach ?>
						</tbody>
					<?php endif ?>
				</table>
			</div>
		</div>
	</div>
<?php endif ?>
