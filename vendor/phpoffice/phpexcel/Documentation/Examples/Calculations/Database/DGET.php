<?php

error_reporting(E_ALL);
set_time_limit(0);

date_default_timezone_set('Europe/London');


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<title>PHPExcel Calculation Examples</title>

</head>
<body>

<h1>DGET</h1>
<h2>Extracts a single value from a column of a list or database that matches conditions that you specify.</h2>
<?php

/** Include path **/
set_include_path(get_include_path() . PATH_SEPARATOR . '../../../../Classes/');

/** Include PHPExcel */
include 'PHPExcel.php';


// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$worksheet = $objPHPExcel->getActiveSheet();

// Add some data
$database = array( array( 'Tree',  'Height', 'Age', 'Yield', 'Profit' ),
                   array( 'Apple',  18,       20,    14,      105.00  ),
                   array( 'Pear',   12,       12,    10,       96.00  ),
                   array( 'Cherry', 13,       14,     9,      105.00  ),
                   array( 'Apple',  14,       15,    10,       75.00  ),
                   array( 'Pear',    9,        8,     8,       76.80  ),
                   array( 'Apple',   8,        9,     6,       45.00  ),
                 );
$criteria = array( array( 'Tree',      'Height', 'Age', 'Yield', 'Profit', 'Height' ),
                   array( '="=Apple"', '>10',    NULL,  NULL,    NULL,     '<16'    ),
                   array( '="=Pear"',  NULL,     NULL,  NULL,    NULL,     NULL     )
                 );

$worksheet->fromArray( $criteria, NULL, 'A1' );
$worksheet->fromArray( $database, NULL, 'A4' );

$worksheet->setCellValue('A12', 'The height of the Apple tree between 10\' and 16\' tall');
$worksheet->setCellValue('B12', '=DGET(A4:E10,"Height",A1:F2)');


echo '<hr />';


echo '<h4>Database</h4>';

$databaseData = $worksheet->rangeToArray('A4:E10',null,true,true,true);
var_dump($databaseData);


echo '<hr />';


// Test the formulae
echo '<h4>Criteria</h4>';

echo 'ALL' . '<br /><br />';

echo $worksheet->getCell("A12")->getValue() .'<br />';
echo 'DMAX() Result is ' . $worksheet->getCell("B12")->getCalculatedValue() .'<br /><br />';

echo '<h4>Criteria</h4>';

$criteriaData = $worksheet->rangeToArray('A1:A2',null,true,true,true);
var_dump($criteriaData);

echo $worksheet->getCell("A13")->getValue() .'<br />';
echo 'DMAX() Result is ' . $worksheet->getCell("B13")->getCalculatedValue();


?>
<body>
</html>