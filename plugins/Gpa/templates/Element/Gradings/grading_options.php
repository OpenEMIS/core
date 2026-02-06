<?php use Cake\Utility\Inflector;?>

<?php if ($action == 'add' || $action == 'edit') : ?>
	<style>
		table .error-message-in-table {
			min-width: 100px;
			width: 100%;
		}
		table th label.table-header-label {
		  background-color: transparent;
		  border: medium none;
		  margin: 0;
		  padding: 0;
		}
	</style>

	<div class="input clearfix">
		<div class="clearfix">
		<?php
			echo $this->Form->input(__('Add New Option'), [
				'label' => __('Grading Options'),
				'type' => 'button',
				'class' => 'btn btn-default',
				'aria-expanded' => 'true',
				'onclick' => "$('#reload').val('reload').click();",
				'required' =>'required'
			]);
			$this->Form->unlockField('GpaGradingTypes.grading_options');
		?>
		</div>
		<div class="table-wrapper full-width">
			<div class="table-responsive">
			    <table class="table table-curved table-input row-align-top">
					<thead>
						<tr>
							<?php foreach ($attr['formFields'] as $formField) : ?>
								<?php if ($attr['fields'][$formField]['type']!='hidden') : ?>
								<?php
									$thClass = (isset($attr['fields'][$formField]['required']) && $attr['fields'][$formField]['required']) ? 'required' : '';
								?>
									<th class="<?= $thClass ?>"><label class="table-header-label"><?= __(Inflector::humanize($formField)) ?></label></th>
									<th></th>
								<?php endif; ?>
							<?php endforeach;?>

							<th class="cell-delete"></th>
						</tr>
					</thead>

					<tbody id='table_grading_options'>

						<?php
						if (!empty($data->grading_options) && is_countable($data->grading_options) && count($data->grading_options) > 0) :
							// iterate each row
							foreach ($data->grading_options as $key => $record) :
								$rowErrors = $record->getErrors();
								if ($rowErrors) {
									$trClass = 'error';
								} else {
									$trClass = '';
								}
						?>
						<tr class="<?= $trClass ?>">

							<?php
								// iterate each field in a row
								foreach ($attr['formFields'] as $i):

									$field = $attr['fields'][$i];
									$fieldErrors = $record->getErrors($field['field']);
									if ($fieldErrors) {
										$tdClass = 'error';
										$fieldClass = 'form-error';
									} else {
										$tdClass = '';
										$fieldClass = '';
									}

						$fieldAttributes = isset($field['attr']) ? $field['attr'] : [];
						$options = array_merge([
										'label'=>false,
										'name'=>'GpaGradingTypes[grading_options]['.$key.']['.$field['field'].']',
										'class'=>$fieldClass,
										'value'=>$record->{$field['field']}
									],
									$fieldAttributes);
							?>
								<?php if ($field['type']!='hidden') : ?>

									<td class="<?= $tdClass ?>">
										<?= $this->HtmlField->{$field['type']}('edit', $record, $field, $options); ?>
									</td>

									<td class="<?= $tdClass ?>">
									    <?php if ($fieldErrors) : ?>
									        <ul>
									            <?php foreach ($fieldErrors as $error) : ?>
									                <?php if (is_array($error)) : ?>
									                    <?php foreach ($error as $subError) : ?>
									                        <li><?= h(__($subError)) ?></li>
									                    <?php endforeach; ?>
									                <?php else : ?>
									                    <li><?= h(__($error)) ?></li>
									                <?php endif; ?>
									            <?php endforeach; ?>
									        </ul>
									    <?php else : ?>
									        &nbsp;
									    <?php endif; ?>
									</td>


								<?php else : ?>
									<?= $this->HtmlField->{$field['type']}('edit', $record, $field, $options);?>
								<?php endif; ?>

							<?php endforeach;?>

							<td>
								<?php
									if ($action == 'edit' || $action == 'add') {
										if (!is_null($gradingOptions)) {
											// check the value of the gradingOptions, if have association will return true, and display 'in use'
											if ($gradingOptions[$data->grading_options[$key]['id']]) {
												echo __('In use');
											} else {
												echo $this->Form->input('Delete', [
													'label' => false,
													'type' => 'button',
													'class' => 'btn btn-dropdown action-toggle btn-single-action',
													'title' => "Delete",
													'aria-expanded' => 'true',
													'onclick' => "jsTable.doRemove(this); "
												]);
											}
										}
									}
								?>
							</td>
						</tr>
						<?php
							endforeach;
						endif;
						?>

					</tbody>

				</table>
			</div>
		</div>
	</div>

<?php else : ?>

	<div class="table-in-view">
		<table class="table">
			<thead>
				<tr>
					<?php foreach ($attr['formFields'] as $formField) : ?>
						<th><?= __(Inflector::humanize(str_replace('_id', '', $formField))) ?></th>
					<?php endforeach;?>
				</tr>
			</thead>
			<tbody>
			<?php
			if (count($data->grading_options)>0) :
				// iterate each row
				foreach ($data->grading_options as $key => $record) :
			?>
				<tr>

				<?php
					// iterate each field in a row
					foreach ($attr['formFields'] as $formField):
						$field = $attr['fields'][$formField];
				?>

					<td><?= $this->HtmlField->{$field['type']}('view', $record, $field, ['label'=>false, 'name'=>'']); ?></td>

				<?php endforeach;?>

				</tr>
			<?php
				endforeach;
			endif;
			?>
			</tbody>
		</table>
	</div>

<?php endif ?>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function () {
        const tableBody = document.getElementById("table_grading_options");
        const gradingTypeMaxField = document.getElementById("gpagradingtypes-max");
        const saveButton = document.querySelector(".btn-save");

        function isFieldVisible(field) {
            return field && field.offsetParent !== null; // Checks if the field exists and is visible
        }

        function validateRow(row) {
            const nameField = row.querySelector("[name*='[name]']");
            const minField = row.querySelector("[name*='[min]']");
            const maxField = row.querySelector("[name*='[max]']");
            const nameError = "This field cannot be left empty";
            const decimalError = "Value must be a valid decimal, and min value cannot be greater than max value";
            const rangeError = "Value must be between 0 and 9999.99";
            const maxValueError = "Grading Option max value cannot exceed the Grading Type max value";

            let errors = [];

            // Validate the name field
            if (isFieldVisible(nameField) && !nameField.value.trim()) {
                errors.push({ field: nameField, message: nameError });
            }

            // Validate the min and max fields
            const minValue = parseFloat(minField?.value);
            const maxValue = parseFloat(maxField?.value);
            const gradingTypeMaxValue = parseFloat(gradingTypeMaxField?.value);

            if (isFieldVisible(minField) && (isNaN(minValue) || minValue < 0 || minValue > 9999.99)) {
                errors.push({ field: minField, message: decimalError });
                errors.push({ field: minField, message: rangeError });
            }

            if (isFieldVisible(maxField) && (isNaN(maxValue) || maxValue < 0 || maxValue > 9999.99 || maxValue < minValue)) {
                errors.push({ field: maxField, message: decimalError });
                errors.push({ field: maxField, message: rangeError });
            }

            // Check if the Grading Option max exceeds the Grading Type max
            if (
                isFieldVisible(maxField) &&
                isFieldVisible(gradingTypeMaxField) &&
                !isNaN(maxValue) &&
                !isNaN(gradingTypeMaxValue) &&
                maxValue > gradingTypeMaxValue
            ) {
                errors.push({ field: maxField, message: maxValueError });
            }

            // Clear previous errors
            row.querySelectorAll(".error-message").forEach((el) => el.remove());

            // Display new errors
            errors.forEach((error) => {
                const errorElement = document.createElement("ul");
                errorElement.className = "error-message";
                const listItem = document.createElement("li");
                listItem.textContent = error.message;
                errorElement.appendChild(listItem);
                error.field.parentElement.appendChild(errorElement);
            });

            // Add or remove error class
            if (errors.length > 0) {
                row.classList.add("error");
            } else {
                row.classList.remove("error");
            }

            return errors.length === 0; // Return whether the row is valid
        }

        // Validate all rows when the Save button is clicked
        saveButton.addEventListener("click", function (event) {
            const rows = tableBody.querySelectorAll("tr");
            let isValid = true;

            rows.forEach((row) => {
                if (!validateRow(row)) {
                    isValid = false;
                }
            });

            // Prevent form submission if there are errors
            if (!isValid) {
                event.preventDefault();
               // alert("Please fix the errors in the form before saving.");
            }
        });

        // Validate a row on input change
        tableBody.addEventListener("input", function (event) {
            const row = event.target.closest("tr");
            validateRow(row);
        });

        // Validate all rows when the Grading Type max value changes
        gradingTypeMaxField.addEventListener("input", function () {
            const rows = tableBody.querySelectorAll("tr");
            rows.forEach((row) => validateRow(row));
        });
        const alertMessage = document.querySelector(".alert.alert-warning");

        // Check if the alert contains the placeholder message
        if (alertMessage && alertMessage.innerHTML.includes("[Message Not Found]")) {
            alertMessage.innerHTML = alertMessage.innerHTML.replace(/\[Message Not Found\]/g, "Grading option is required");
        }
        const errorElements = document.querySelectorAll("td.error");

        // Iterate over each element and hide it
        errorElements.forEach(function (element) {
            element.style.display = "none"; // Hide the error element
        });
    });
</script>

