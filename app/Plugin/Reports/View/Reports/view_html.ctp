<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
//pr($params);
//phpinfo();die;

if (($handle = fopen($params['path'] . $params['id'], "r")) !== FALSE) {
	//$test = file_get_contents($params['path'] . $params['id']);
	//$test_array = explode("\n", $test);
	//print_r($test_array);
	//die;
	//$fp = file($params['path'] . $params['id']);
	//$totalRows = count($fp);
	$filePath = $params['path'] . $params['id'];

	$cmd = 'wc -l '; // shell command, doesn't work in windows
	$result = trim(exec($cmd . $filePath));
	$resultArray = explode(' ', $result);
	$totalRows = $resultArray[0] - 2;
	//pr($totalRows);
	if ($totalRows <= 1) {
		echo $this->Label->get('ReportInHtml.no_data');
	} else {
		$file = new SplFileObject($params['path'] . $params['id']);

		$firstRow = 0;
		$file->seek($firstRow);
		// rows per page
		$rowsPerPage = 100;

		$arrHeader = explode(',', $file->current());
		$totalColumns = count($arrHeader);

		$fileName = $this->params->pass[0];

		if (isset($this->params->pass[1]) && intval($this->params->pass[1]) !== 0) {
			$currentPage = intval($this->params->pass[1]);
		} else {
			$currentPage = 1;
		}

		$totalPages = ceil($totalRows / $rowsPerPage);

		if ($currentPage > $totalPages) {
			$currentPage = 1;
		}

		$dataRowStart = ($currentPage - 1) * $rowsPerPage + 1;

		// generate pagination

		$paginationStr = '';
		$paginationStr .= '<div class="row">';
		$paginationStr .= '<ul id="pagination">';

		//$numOfLinkFront = 10;

		if ($currentPage > 1) {
			$paginationStr .= '<li class="">' . $this->Html->link(__('Previous'), array($fileName, ($currentPage - 1)), array('class' => '')) . '</li>';
		}

		if ($currentPage < 6) {

			if (($currentPage - 2) > 0) {
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 2, array($fileName, ($currentPage - 2)), array('class' => '')) . '</li>';
			}

			if (($currentPage - 1) > 0) {
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 1, array($fileName, ($currentPage - 1)), array('class' => '')) . '</li>';
			}

			$paginationStr .= '<li class="current">' . $currentPage . '</li>';

			if (($currentPage + 1) <= $totalPages) {
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 1, array($fileName, ($currentPage + 1)), array('class' => '')) . '</li>';
			}

			if (($currentPage + 2) <= $totalPages) {
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 2, array($fileName, ($currentPage + 2)), array('class' => '')) . '</li>';
			}

			$paginationStr .= '<li><span class="ellipsis">...</span></li>';

			$paginationStr .= '<li class="">' . $this->Html->link($totalPages, array($fileName, $totalPages), array('class' => '')) . '</li>';
		} else {
			for ($i = 1; $i < 3; $i++) {
				if ($currentPage == $i) {
					$paginationStr .= '<li class="current">' . $i . '</li>';
				} else {
					$paginationStr .= '<li class="">' . $this->Html->link($i, array($fileName, $i), array('class' => '')) . '</li>';
				}
			}

			$paginationStr .= '<li><span class="ellipsis">...</span></li>';

			if ($currentPage < ($totalPages - 3)) {

				$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 2, array($fileName, ($currentPage - 2)), array('class' => '')) . '</li>';
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 1, array($fileName, ($currentPage - 1)), array('class' => '')) . '</li>';
				$paginationStr .= '<li class="current">' . $currentPage . '</li>';
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 1, array($fileName, ($currentPage + 1)), array('class' => '')) . '</li>';
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 2, array($fileName, ($currentPage + 2)), array('class' => '')) . '</li>';

				$paginationStr .= '<li><span class="ellipsis">...</span></li>';

				$paginationStr .= '<li class="">' . $this->Html->link($totalPages, array($fileName, $totalPages), array('class' => '')) . '</li>';
			} else if ($currentPage >= ($totalPages - 3)) {
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 3, array($fileName, ($currentPage - 3)), array('class' => '')) . '</li>';
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 2, array($fileName, ($currentPage - 2)), array('class' => '')) . '</li>';
				$paginationStr .= '<li class="">' . $this->Html->link($currentPage - 1, array($fileName, ($currentPage - 1)), array('class' => '')) . '</li>';
				$paginationStr .= '<li class="current">' . $currentPage . '</li>';
				if (($currentPage + 1) <= $totalPages) {
					$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 1, array($fileName, ($currentPage + 1)), array('class' => '')) . '</li>';
				}

				if (($currentPage + 2) <= $totalPages) {
					$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 2, array($fileName, ($currentPage + 2)), array('class' => '')) . '</li>';
				}

				if (($currentPage + 3) <= $totalPages) {
					$paginationStr .= '<li class="">' . $this->Html->link($currentPage + 3, array($fileName, ($currentPage + 3)), array('class' => '')) . '</li>';
				}
			}
		}

		if ($currentPage <= ($totalPages - 1)) {
			$paginationStr .= '<li class="">' . $this->Html->link(__('Next'), array($fileName, ($currentPage + 1)), array('class' => '')) . '</li>';
		}

		$paginationStr .= '</ul>';
		$paginationStr .= '</div>';

		if($totalPages > 1){
			echo $paginationStr;
		}
		?>
		<table class="table reportHtml">
			<thead class="table_head">
				<tr>
					<?php
					foreach ($arrHeader AS $column) :
						?>
						<th><?php echo $column; ?></th>
						<?php
					endforeach;
					?>
				</tr>
			</thead>
			<tbody>
				<?php
				$file->seek($dataRowStart);

				for ($i = 0; $i < $rowsPerPage; $i++) {
					$arrCurrentRow = explode(',', $file->current());
					if ((count($arrCurrentRow) !== 0) && (!empty($arrCurrentRow[0]))) {
						$tempColumns = count($arrCurrentRow);
						while ($tempColumns < $totalColumns) {
							$arrCurrentRow[] = '';
							$tempColumns++;
						}

						if ($tempColumns > $totalColumns) {
							array_pop($arrCurrentRow);
						}
						//pr($arrCurrentRow);
						//echo '<br>';
						?>
						<tr>
							<?php
							foreach ($arrCurrentRow AS $column) :
								?>
								<td><?php echo $column; ?></td>
								<?php
							endforeach;
							?>
						</tr>
						<?php
					}
					$file->seek(++$dataRowStart);
				}
				?>
			</tbody>
		</table>
		<?php 
		if($totalPages > 1){
			echo $paginationStr;
		}
		fclose($handle);
	}
} else {
	echo $this->Label->get('ReportInHtml.failed_open_file');
}
?>