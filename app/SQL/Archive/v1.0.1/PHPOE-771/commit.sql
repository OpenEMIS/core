UPDATE `batch_reports` 
SET `query` = '$this->Student->formatResult = true;
		$data = $this->Student->find(''all'', array(
			''fields'' => array(
				''Student.identification_no AS IdentificationNo'',
				''Student.first_name AS FirstName'',
				''Student.last_name AS LastName'',
				''Student.gender AS Gender'',
				''Student.date_of_birth AS DateOfBirth'',
				''Student.address AS Address'',
				''Student.postal_code AS PostalCode'',
				''AddressArea.name AS AddressArea'',
				''BirthplaceArea.name AS BirthplaceArea''
			),
			''joins'' => array(
				array(
					''table'' => ''areas'',
					''alias'' => ''AddressArea'',
					''type'' => ''LEFT'',
					''conditions'' => array(''AddressArea.id = Student.address_area_id'')
				),
				array(
					''table'' => ''areas'',
					''alias'' => ''BirthplaceArea'',
					''type'' => ''LEFT'',
					''conditions'' => array(''BirthplaceArea.id = Student.birthplace_area_id'')
				),
				array(
					''table'' => ''institution_site_students'',
					''alias'' => ''institutionSiteStudent'',
					''type'' => ''LEFT'',
					''conditions'' => array(''institutionSiteStudent.student_id = Student.id'')
				)
			)
			,''group'' => array(''Student.id'')
			,''conditions'' => array(''OR''=>array(''not'' => array(''institutionSiteStudent.student_status_id'' => ''1''), ''institutionSiteStudent.student_id'' => NULL))
		));', 
`template` = 'IdentificationNo,FirstName,LastName,Gender,DateOfBirth,Address,AddressArea,BirthplaceArea,PostalCode' 
WHERE `batch_reports`.`id` = 1028;