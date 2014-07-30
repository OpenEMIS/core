<h3 class="reportHtmlTitle"><?php echo __($pageTitleReport); ?></h3>
<?php
// pagination start
if ($totalRows < 1) {
	echo $this->Label->get('ReportInHtml.no_data');
} else {
	// generate pagination
	$paginationStr = '';
	$paginationStr .= '<div class="row">';
	$paginationStr .= '<ul id="pagination">';

	//$numOfLinkFront = 10;

	if ($currentPage > 1) {
		$paginationStr .= '<li class="">' . $this->Html->link(__('Previous'), array($param1, $param2, ($currentPage - 1)), array('class' => '')) . '</li>';
	}

	if ($currentPage < 6) {

		if (($currentPage - 2) > 0) {
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 2, array($param1, $param2, ($currentPage - 2)), array('class' => '')) . '</li>';
		}

		if (($currentPage - 1) > 0) {
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 1, array($param1, $param2, ($currentPage - 1)), array('class' => '')) . '</li>';
		}

		$paginationStr .= '<li class="current">' . $currentPage . '</li>';

		if (($currentPage + 1) <= $totalPages) {
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 1, array($param1, $param2, ($currentPage + 1)), array('class' => '')) . '</li>';
		}

		if (($currentPage + 2) <= $totalPages) {
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 2, array($param1, $param2, ($currentPage + 2)), array('class' => '')) . '</li>';
		}

		if ($totalPages > ($currentPage + 2)) {
			$paginationStr .= '<li><span class="ellipsis">...</span></li>';
			$paginationStr .= '<li class="">' . $this->Html->link($totalPages, array($param1, $param2, $totalPages), array('class' => '')) . '</li>';
		}
	} else {
		for ($i = 1; $i < 3; $i++) {
			if ($currentPage == $i) {
				$paginationStr .= '<li class="current">' . $i . '</li>';
			} else {
				$paginationStr .= '<li class="">' . $this->Html->link($i, array($param1, $param2, $i), array('class' => '')) . '</li>';
			}
		}

		$paginationStr .= '<li><span class="ellipsis">...</span></li>';

		if ($currentPage < ($totalPages - 3)) {

			$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 2, array($param1, $param2, ($currentPage - 2)), array('class' => '')) . '</li>';
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 1, array($param1, $param2, ($currentPage - 1)), array('class' => '')) . '</li>';
			$paginationStr .= '<li class="current">' . $currentPage . '</li>';
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 1, array($param1, $param2, ($currentPage + 1)), array('class' => '')) . '</li>';
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 2, array($param1, $param2, ($currentPage + 2)), array('class' => '')) . '</li>';

			$paginationStr .= '<li><span class="ellipsis">...</span></li>';

			$paginationStr .= '<li class="">' . $this->Html->link($totalPages, array($param1, $param2, $totalPages), array('class' => '')) . '</li>';
		} else if ($currentPage >= ($totalPages - 3)) {
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 3, array($param1, $param2, ($currentPage - 3)), array('class' => '')) . '</li>';
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 2, array($param1, $param2, ($currentPage - 2)), array('class' => '')) . '</li>';
			$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 1, array($param1, $param2, ($currentPage - 1)), array('class' => '')) . '</li>';
			$paginationStr .= '<li class="current">' . $currentPage . '</li>';
			if (($currentPage + 1) <= $totalPages) {
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 1, array($param1, $param2, ($currentPage + 1)), array('class' => '')) . '</li>';
			}

			if (($currentPage + 2) <= $totalPages) {
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 2, array($param1, $param2, ($currentPage + 2)), array('class' => '')) . '</li>';
			}

			if (($currentPage + 3) <= $totalPages) {
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 3, array($param1, $param2, ($currentPage + 3)), array('class' => '')) . '</li>';
			}
		}
	}

	if ($currentPage <= ($totalPages - 1)) {
		$paginationStr .= '<li class="">' . $this->Html->link(__('Next'), array($param1, $param2, ($currentPage + 1)), array('class' => '')) . '</li>';
	}

	$paginationStr .= '</ul>';
	$paginationStr .= '</div>';
}
// pagination end
?>
<?php
if (isset($totalPages) && $totalPages > 1):
	echo $paginationStr;
endif;
?>
<div id="reportManagerDisplay">
	<?php
	$counter = 0;
	$columns = 0;
	$floatFields = array();
	?>     
	<?php if (!empty($reportData)): ?>
		<table class="table" style="width: auto;">
			<thead>
				<tr>
					<?php foreach ($fieldList as $field): ?>
						<td>
							<?php
							$columns++;
							$modelClass = substr($field, 0, strpos($field, '.'));
							$displayField = strtolower(substr($field, strpos($field, '.') + 1));
							$displayField = ( isset($labelFieldList[$modelClass][$displayField]) ? $labelFieldList[$modelClass][$displayField] : ( isset($labelFieldList['*'][$displayField]) ? $labelFieldList['*'][$displayField] : $displayField ));
							$displayField = str_replace('_', ' ', $displayField);
							$displayField = ucfirst($displayField);
							$modelClass = Inflector::humanize(Inflector::underScore($modelClass));
							echo $modelClass . ' ' . $displayField;
							if ($fieldsType[$field] == 'float'): // init array for float fields sum
								$floatFields[$field] = 0;
							endif;
							?>
						</td>
					<?php endforeach; ?>
				</tr>
			</thead>
			<?php
			$i = 0;
			foreach ($reportData as $reportItem):
				$counter++;
				$class = null;
				if ($i++ % 2 == 0):
					$class = ' altrow';
				endif;
				?>
				<tr class="body<?php echo $class; ?>">
					<?php foreach ($fieldList as $field): ?>
						<td>
							<?php
							$params = explode('.', $field);
							if ($fieldsType[$field] == 'float'):
								echo $this->element('format_float', array('f' => $reportItem[$params[0]][$params[1]]));
								$floatFields[$field] += $reportItem[$params[0]][$params[1]];
							else:
								echo $reportItem[$params[0]][$params[1]];
							endif;
							?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			<?php if (count($floatFields) > 0): ?>
				<tr class="footer">
					<?php foreach ($fieldList as $field): ?>
						<td>
							<?php
							if ($fieldsType[$field] == 'float'):
								echo $this->element('format_float', array('f' => $floatFields[$field]));
							endif;
							?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endif; ?>
		</table>
	<?php endif; ?>
</div>
<?php
if (isset($totalPages) && $totalPages > 1):
	echo $paginationStr;
endif;
?>