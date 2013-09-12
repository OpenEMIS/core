<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppTask', 'Console/Command/Task');

class PdfTask extends AppTask {
	public $tasks = array('Mpdf');
	
	public function renderPdf($item) {
		$query = $item['query'];
		$template = $item['template'];
		if(!is_null($query)) {
			eval($query);
			if(isset($vars)) {
				foreach($vars as $key => $value) {
					$template = str_replace('[{'.$key.'}]', $value, $template);
				}
			}
		}
		return $template;
	}
	
	public function genPDF($settings){
		$BatchReport = ClassRegistry::init('DataProcessing.BatchReport');
		$ConfigItem = ClassRegistry::init('ConfigItem');
		$reportItems = $BatchReport->find('all', array(
			'recursive' => -1,
			'conditions' => array('BatchReport.report_id' => $settings['reportId']),
			'order' => array('BatchReport.order')
		));
		
		$module = str_replace(" ", "_", $settings['module']);
		$category = str_replace(" ", "_", $settings['category']);
		$filename = str_replace(" ", "_", $settings['name']);
		$file = sprintf('%s/reports/%s/%s/%d_%d_%s.pdf', WWW_ROOT, $category, $module, $settings['reportId'], $settings['batchProcessId'], $filename);
		$config = $ConfigItem->find('first', array(
			'fields' => array('ConfigItem.value', 'ConfigItem.default_value'),
			'conditions' => array(
				'ConfigItem.type' => $settings['name'],
				'ConfigItem.label' => 'Page Orientation'
			)
		));
		
		$orientation = '';
		if($config) {
			$orientation = !empty($config['ConfigItem']['value']) ? $config['ConfigItem']['value'] : $config['ConfigItem']['default_value'];
		}
		$this->Mpdf->init();
        $this->Mpdf->AddPage($orientation==='L' ? $orientation : '');
		foreach($reportItems as $item) {
			$report = $item['BatchReport'];
			$html = $this->renderPdf($report);
			if($report['name'] === 'Styles') {
				$this->Mpdf->WriteHTML($html, 1);
			} else {
				$this->Mpdf->WriteHTML($html);
			}
		}
		$this->Mpdf->Output($file, 'F');
    }
}
	
?>
