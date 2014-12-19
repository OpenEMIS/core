UPDATE `batch_reports` SET `template` = '<div class="content">
	<div class="header">[{header}]</div>
	<div class="section">
		<div class="section_head">[{sectionHead_1}]</div>
		<div class="legend">[{legend}]</div>
		<table>
			<tr class="head"><td>Area</td>[{tableHead_1}]</tr>
			[{tableData_1}]
		</table>
	</div>
	<div class="section">
		<div class="section_head">[{sectionHead_2}]</div>
		<table>
			<tr class="head"><td>Area</td>[{tableHead_2}]</tr>
			[{tableData_2}]
		</table>
	</div>
</div>' WHERE `batch_reports`.`id` = 115;

UPDATE `batch_reports` SET `template` = '<div class="content">
	<div class="header">[{header}]</div>
	<div class="section">
		<div class="section_head">[{sectionHead_1}]</div>
		<table>
			<tr class="head"><td>Area</td>[{tableHead_1}]</tr>
			[{tableData_1}]
		</table>
	</div>
	<div class="section">
		<div class="section_head">[{sectionHead_2}]</div>
		<table>
			<tr class="head"><td>Area</td>[{tableHead_1}]</tr>
			[{tableData_2}]
		</table>
	</div>
</div>' WHERE `batch_reports`.`id` = 118;

UPDATE `batch_reports` SET `template` = '<div class="content">
	<div class="header">[{header}]</div>
	<div class="section">
		<div class="section_head">[{sectionHead_1}]</div>
		<div class="legend">[{legend}]</div>
		<table>
			<tr class="head"><td>Area</td>[{tableHead_1}]</tr>
			[{tableData_1}]
		</table>
	</div>
	<div class="section">
		<div class="section_head">[{sectionHead_2}]</div>
		<div class="legend">[{legend}]</div>
		<table>
			<tr class="head"><td>Area</td>[{tableHead_1}]</tr>
			[{tableData_2}]
		</table>
	</div>
</div>' WHERE `batch_reports`.`id` = 119;

UPDATE `batch_reports` SET `template` = '<div class="content last_element">
	<div class="header">[{header}]</div>
	<div class="section">
		<div class="section_head">[{sectionHead_1}]</div>
		<div class="legend">[{legend}]</div>
		<table>
			<tr class="head"><td>Area</td>[{tableHead_1}]</tr>
			[{tableData_1}]
		</table>
	</div>
	<div class="section last_element">
		<div class="section_head">[{sectionHead_2}]</div>
		<div class="legend">[{legend}]</div>
		<table>
			<tr class="head"><td>Area</td>[{tableHead_1}]</tr>
			[{tableData_2}]
		</table>
	</div>
</div>' WHERE `batch_reports`.`id` = 120;