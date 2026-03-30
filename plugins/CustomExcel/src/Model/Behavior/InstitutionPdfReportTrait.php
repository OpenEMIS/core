<?php
namespace CustomExcel\Model\Behavior;

use Cake\Log\Log;
use Cake\ORM\TableRegistry; // POCOR-9336
use PhpOffice\PhpSpreadsheet\Spreadsheet; // POCOR-9336
use PhpOffice\PhpSpreadsheet\Writer\Exception; // POCOR-9336
use PhpOffice\PhpSpreadsheet\IOFactory; // POCOR-9336


/*
    This trait is for ExcelReportBehavior.php
    To separate PDF logic
*/
trait InstitutionPdfReportTrait
{
    const PRINTER_MPDF = 1; // POCOR-9336
    const PRINTER_LIBREOFFICE = 2;// POCOR-9336
    const PRINTER_EXTERNAL = 3; // POCOR-9336

    private $currentWorksheet = null;
    private $currentWorksheetIndex = 0;

    private $excelLastRowValueArr = [];
    private $lastColumn = 0;

    private $alphabetValueArr = [
        'A' => '1',
        'B' => '2',
        'C' => '3',
        'D' => '4',
        'E' => '5',
        'F' => '6',
        'G' => '7',
        'H' => '8',
        'I' => '9',
        'J' => '10',
        'K' => '11',
        'L' => '12',
        'M' => '13',
        'N' => '14',
        'O' => '15',
        'P' => '16',
        'Q' => '17',
        'R' => '18',
        'S' => '19',
        'T' => '20',
        'U' => '21',
        'V' => '22',
        'W' => '23',
        'X' => '24',
        'Y' => '25',
        'Z' => '26',
    ];

    private function checkLastColumn($targetColumnValue)
    {
        $tens = 0;
        $columnToRemoveOnwards = 0; // instead of $value

        // convert $targetColumnValue to numeric value. E.g AA = 27
        for ($i = strlen($targetColumnValue)-1; $i >= 0; $i--) {
            $alphabet = $targetColumnValue[$i];
            $alphabetColumnValue = $this->alphabetValueArr[$alphabet];

            $columnToRemoveOnwards += $alphabetColumnValue * pow(count($this->alphabetValueArr), $tens++);
        }

        if ($columnToRemoveOnwards > $this->lastColumn) {
            $this->lastColumn = $columnToRemoveOnwards;
        }
    }

    private function checkLastRow($targetRowValue)
    {
        if (isset($this->excelLastRowValueArr[$this->currentWorksheetIndex]) && $targetRowValue < $this->excelLastRowValueArr[$this->currentWorksheetIndex] ) {
            return;
        }
        $this->excelLastRowValueArr[$this->currentWorksheetIndex] = $targetRowValue;
    }



    private function checkIfNoBorder($cssString)
    {
        $positions = ['border-left:none', 'border-right:none', 'border-bottom:none', 'border-top:none'];

        foreach ($positions as $position) {
            if (strpos($cssString, $position) === false) {
                return false;
            }
        }

        return true;
    }

    private function extractBorderStyle($headerString)
    {
      $styleList = [
        'td' => []
      ];
      $maxValue = 9999;

      for ($id = 0; $id < $maxValue; $id++) {
        $targetCssStartTag = 'td.style';
        $targetCssEndTag = 'th.style';

        $targetCssStartTag .= $id;
        $targetCssEndTag .= $id;

        // Get the start tag position
        $targetCssStartPos = strpos($headerString, $targetCssStartTag);

        // Get the end tag position
        $targetCssEndPos = strpos($headerString, $targetCssEndTag);

        // Get the whole CSS style
        $targetCss = substr($headerString, $targetCssStartPos, $targetCssEndPos - $targetCssStartPos);

        if (empty($targetCss)) {
            // When hit until the last Row ID it will stop extracting the border style
            break;
        } else {  // Extract all the style within this tag
          $regexRemoveCssTag= preg_replace("/(".$targetCssStartTag." { )/", '', $targetCss);
          $regexAddStyle= preg_replace("/( })/", '', $regexRemoveCssTag);

          $styleList['td'][$id] = [
            'style' => $regexAddStyle,
            'hasBorder' => !$this->checkIfNoBorder($regexAddStyle)
          ];
        }
      }
      return $styleList;
    }

    private function processHtml($htmlFile, $sheetIndex = 0)
    {
        $processingHtml = $htmlFile;
        $searchHeadString = '<tbody>';
        $searchTailString = '</tbody>';
        $searchHeadLength = strlen($searchHeadString);

        // Process Head
        $headPos = strpos($processingHtml, $searchHeadString);
        $headString = substr($processingHtml, 0, $headPos + $searchHeadLength); // Head

        // Process Tail
        $tailPos = strpos($processingHtml, $searchTailString);
        $tailString = substr($processingHtml, $tailPos);  // Tail

        // Process String
        $processingString = substr($processingHtml, $headPos + $searchHeadLength, $tailPos - $headPos - $searchHeadLength);

        // To remove Column and Row
        $processingString = $this->removeColumnAndRow($processingString, $sheetIndex);

        // Remove any cells that is empty and do not belongs to any style classes css
        $processedString = $this->removeEmptyCells($processingString, $headString);

        // To change the border to solid line instead of dotted line
        $processedHeadString = $this->styleBorderToSolid($headString);

        // To remove empty page at the end of the pdf
        $searchFormat = 'page-break-after:always';
        $processedHeadString = str_replace($searchFormat, '', $processedHeadString);

        // Combined all the processed Head, Body, Tail html into one
        $processedHtml = $processedHeadString.$processedString.$tailString;
        return $processedHtml;
    }

    private function styleBorderToSolid($headString)
    {
        // To make the excel sheet to solid sheet
        $searchFormat = '.gridlines td { border:1px dotted black }';
        $replaceFormat = '.gridlines td { border:1px solid black }';
        $headString = str_replace($searchFormat, $replaceFormat, $headString);

        $searchFormat = '.gridlines th { border:1px dotted black }';
        $replaceFormat = '.gridlines th { border:1px solid black }';
        $headString = str_replace($searchFormat, $replaceFormat, $headString);

        // To add abit of padding to make the text nicer
        $searchFormat = '<style>';
        $replaceFormat = '<style> td { padding: 5px !important}';
        $headString = str_replace($searchFormat, $replaceFormat, $headString);

        return $headString;
    }

    private function removeEmptyCells($processingString, $headString)
    {

        $searchString = '">&nbsp;</td>';    // dotted lines
        $replaceString = '" style="border:none !important;">&nbsp;</td>';
        $processingString = str_replace($searchString, $replaceString, $processingString);

        $styleList = $this->extractBorderStyle($headString);

        foreach ($styleList as $styleTag => $list) {
          $searchFormat = 'style%s null"></%s>';
          $searchFormat2 = 'style%s"></%s>';
          $replaceFormat = 'style%s%s" %s></%s>';

          foreach ($list as $id => $cssObj) {
            // To do a check is because the content cell and normal empty cell having the same style.
            // Therefore, check by their main CSS. To determine which one is content cell or normal empty cell.
            $hasBorderStyle = ($cssObj['hasBorder']) ? ' has-border' : '';
            $borderStyle = ($hasBorderStyle) ? '' : 'style="' . $cssObj['style'] . '"';

            $searchString = sprintf($searchFormat, (string)$id, $styleTag);
            $searchString2 = sprintf($searchFormat2, (string)$id, $styleTag);
            $replaceString = sprintf($replaceFormat, (string)$id, $hasBorderStyle, $borderStyle, $styleTag);

            $processingString = str_replace($searchString, $replaceString, $processingString);
            $processedString = str_replace($searchString2, $replaceString, $processingString);
          }
        }
        return $processedString;
    }

    //  ================ START REMOVE COLUMN AND ROW ================
    private function removeColumnAndRow($processingString, $sheetIndex)
    {
        $processedHtmlRows = [];
        $targetRowValue = $this->excelLastRowValueArr[$sheetIndex+1];

        // Loop from 0 to LastRow to remove column (Row by Row)
        for ($id = 0; $id < $targetRowValue; $id++) {
            $targetRowString = '<tr class="row'.$id.'">';
            $targetRowEndString = '</tr>';
            $targetRowPos = strpos($processingString, $targetRowString);
            $targetRowEndPos = strpos($processingString, $targetRowEndString);

            // Break the loop, if html do not exist current row
            if ($targetRowPos <= 0) {
                break;
            }

            //targetRowTotalLengthPos means I am getting the initial value to the start of </tr> to the end.
            $targetRowTotalLengthPos = $targetRowEndPos + $targetRowPos;

            $targetRow = substr($processingString, 0, $targetRowTotalLengthPos);

            // To generate the regular expression for removing the extra columns in the html format
            $prefixRegex = '/(.*)(column|col)';
            $postfixRegex = '(.*)/';
            $regexString = $this->generateRemovalRegex($prefixRegex, $postfixRegex, $this->lastColumn);

            // To make sure if there's exists a image it will display by removing the 'e'. i.e. jpeg -> jpg
            $searchFormat = '/(<img src="data:image\/).*(;base64)/';
            $replacement = '<img src="data:image/jpg;base64';
            $processedHtmlRow = preg_replace($searchFormat, $replacement, $targetRow);

            $processedHtmlColumn = preg_replace($regexString, "", $processedHtmlRow);

            // Clear up all the empty blank lines using regular expression
            $processedHtmlRows[] = preg_replace('/^\h*\v+/m', "", $processedHtmlColumn);

            // Remove the target row from the main processString
            $processingString = substr_replace($processingString, "", 0, $targetRowTotalLengthPos);
        }

        $processedString = '';
        // Combine back the whole html as a whole
        for ($id = 0; $id < count($processedHtmlRows); $id ++) {
            $processedString .= $processedHtmlRows[$id];
        }

        return $processedString;
    }

    private function generateRemovalRegex($prefixRegex, $postfixRegex, $startColumn, $endingColumnn = 255)
    {
        $regex = $prefixRegex;
        $regex .= $this->regexRange($startColumn, $endingColumnn);
        $regex .= $postfixRegex;

        return $regex;
    }

    private function regexRange($from, $to)
    {
        $ranges = array($from);
        $increment = 1;
        $next = $from;
        $higher = true;

        while(true) {
            $next += $increment;

            if($next + $increment > $to) {
                if( $next <= $to) {
                    $ranges[] = $next;
                }
                $increment /= 10;
                $higher = false;
            } elseif($next % ($increment * 10) === 0) {
                $ranges[] = $next;
                $increment = $higher ? $increment * 10 : $increment / 10;
            }

            if (!$higher && $increment < 10) {
                break;
            }
        }

        $ranges[] = $to + 1;
        $regex = '(';

        for ($i = 0; $i < sizeof($ranges) - 1; $i++) {
            $str_from = (string)($ranges[$i]);
            $str_to = (string)($ranges[$i + 1] - 1);

            for($j = 0; $j < strlen($str_from); $j++) {
                if($str_from[$j] == $str_to[$j]) {
                    $regex .= $str_from[$j];
                } else {
                    $regex .= "[" . $str_from[$j] . "-" . $str_to[$j] . "]";
                }
            }
            $regex .= "|";
        }

        return substr($regex, 0, strlen($regex)-1) . ')';
    }
    //  ================ END REMOVE COLUMN AND ROW ================

    private function savePDF($objSpreadsheet, $filepath, $institution_id)
    {
        Log::write('debug', 'ExcelReportBehavior >>> filepath: '.$filepath);
        // Convert spreadsheet object into html
// POCOR-9336: start . Convert spreadsheet object into pdf
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $printer = $ConfigItems->value('pdf_service');
        switch ($printer) {
            case self::PRINTER_MPDF:
                $pdfContent = $this->printPdfViaMpdf($objSpreadsheet, $filepath, $institution_id);
                break;
            case self::PRINTER_LIBREOFFICE:
                $pdfContent = $this->printPdfViaLibreOffice($objSpreadsheet, $filepath, $institution_id);
                break;
            case self::PRINTER_EXTERNAL:
                $pdfContent = $this->printPdfViaApi($objSpreadsheet, $filepath . '_sheet' . $institution_id);
                break;
        }

        if (!empty($pdfContent)) {
            $filename = $this->getConfig('filename') . '_' . (!empty($institution_id) ? $institution_id : date('Ymd\THis')) . '.txt';
            $outputPath = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS . $filename;
            file_put_contents($outputPath, $pdfContent);
            Log::write('debug', "Saved PDF to: $outputPath");
        } else {
            Log::error("PDF content  is empty");
            throw new \Exception("PDF content is empty"); // POCOR-9598
        }
    }

    private function mergePDFFiles(Array $filenames, $outFile, $title = '', $author = '', $subject = '')
    {
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor($author);
        $mpdf->SetSubject($subject);

        if ($filenames) {
            $filesTotal = sizeof($filenames);
            // $mpdf->SetImportUse();

            for ($i = 0; $i<count($filenames);$i++) {
                $curFile = $filenames[$i];
                if (file_exists($curFile)){
                    $pageCount = $mpdf->SetSourceFile($curFile);
                    for ($p = 1; $p <= $pageCount; $p++) {
                        $tplId = $mpdf->ImportPage($p);
                        $wh = $mpdf->getTemplateSize($tplId);
                        if (($p==1)){
                            $mpdf->state = 0;
                             $mpdf->AddPage('L');

                            $mpdf->UseTemplate ($tplId);
                        }
                        else {
                            $mpdf->state = 1;
                             $mpdf->AddPage('L');

                            $mpdf->UseTemplate($tplId);
                        }
                    }
                }
            }
        }

        $file_path = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS . $outFile.'.pdf';
        $content = $mpdf->Output($file_path, \Mpdf\Output\Destination::STRING_RETURN);
        unset($mpdf);
        return $content;
    }

    /**
     * @param $objSpreadsheet
     * @param $filepath
     * @param $institution_id
     * @throws \Mpdf\MpdfException
     */
    private function printPdfViaMpdf($objSpreadsheet, $filepath, $institution_id)
    { // POCOR-9336 end
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($objSpreadsheet);

        // This is to store to final processedHtml
        $processedHtml = '';
        $filePaths = [];
        $basePath = $filepath;
        for ($sheetIndex = 0; $sheetIndex < $objSpreadsheet->getSheetCount(); $sheetIndex++) {
            $mpdf = new \Mpdf\Mpdf();
            $filepath = $basePath . '_' . $sheetIndex;
            $writer->setSheetIndex($sheetIndex);
            $writer->save($filepath);

            // Read the html file and convert them into a variable
            $file = file_get_contents($filepath, FILE_USE_INCLUDE_PATH);

            // Remove all the redundant rows and columns
            $processedHtml = $this->processHtml($file, $sheetIndex);

            // Save the processed html into a temp pdf
            $mpdf->AddPage('L');

            $mpdf->WriteHTML($processedHtml);
            $filepath = $filepath . '.pdf';

            $mpdf->Output($filepath, 'F');
            $filePaths[] = $filepath;
            unset($mdpf);
        }
        // Merge all the pdf that belongs to one report
        if (!empty($institution_id)) {
            $fileName = $this->getConfig('filename') . '_' . $institution_id;
        } else {
            $fileName = $this->getConfig('filename') . '_' . date('Ymd') . 'T' . date('His');
        }

        Log::write('debug', '----------------------fileName---------------------: ');
        Log::write('debug', $fileName);

        $finalPDF = $this->mergePDFFiles($filePaths, $fileName, $fileName); // POCOR-9336
        // // Remove the temp file that is converted from excel object and its successfully converted to pdf
        if ($this->getConfig('purge')) {
            foreach ($filePaths as $filepath) {
                // delete excel file after successfully converted to pdf
                $this->deleteFile($filepath);
            }
        }
        return $finalPDF; // POCOR-9336 start
    }

    /**
     * Sends an XLSX spreadsheet to the PDF printer API and returns the resulting PDF content.
     *
     * @param Spreadsheet $objSpreadsheet
     * @param string $baseFileName Base path without extension
     * @return string|null PDF binary content or null on failure
     * @throws GuzzleException
     * @throws Exception
     */
    private function printPdfViaApi(Spreadsheet $objSpreadsheet, string $baseFileName): ?string
    {
        Log::write('debug', 'ExcelReportBehavior >>> base filepath: ' . $baseFileName);
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $printer = $ConfigItems->value('pdf_service');

        if ($printer != self::PRINTER_EXTERNAL) {
            return null;
        }

        $attributes = TableRegistry::getTableLocator()
            ->get('Configuration.ExternalDataSourceAttributes')
            ->find('list', ['keyField' => 'attribute_field', 'valueField' => 'value'])
            ->where(['external_data_source_type' => 'PDF Service'])
            ->disableHydration()
            ->toArray();
//        Log::debug(print_r($attributes,true));
        $authUser = $attributes['username'] ?? null;
        $baseUrl = $attributes['api_url'] ?? null;
        $authPass = $attributes['password'] ?? null;
        $apiParams = $attributes['api_params'] ?? null;
        $deleteOriginal = $attributes['delete_original'] ?? 1;
        $sheetPath = $baseFileName . '.xlsx';
        $pdfFile = basename($baseFileName) . '.pdf';
        $pdfUrl = $baseUrl . '/check-pdf/' . $pdfFile. '?delete=true';
//        $authUser = 'user';
//        $authPass = 'password';
        try {
            $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
            $objWriter->save($sheetPath);

            $client = new \GuzzleHttp\Client();
            $multipart = [
                [
                    'name' => 'file',
                    'contents' => fopen($sheetPath, 'r'),
                    'filename' => basename($sheetPath)
                ],
            ];
            if($apiParams){
                $apiParams = json_encode(json_decode($apiParams, true)); // ensures valid JSON string
                if ($apiParams) {
                    $multipart[] = [
                        'name' => 'lo_options',
                        'contents' => $apiParams
                    ];
                }
            }
            if ($deleteOriginal) {
                $multipart[] = [
                    'name'     => 'delete_original',
                    'contents' => '1'
                ];
            }
//            Log::debug(print_r($multipart,true));
            $response = $client->post($baseUrl . '/queue-job', [
                'auth' => [$authUser, $authPass],
                'multipart' => $multipart
            ]);

            // Poll until ready
            sleep(2);
            $retries = 15;
            while ($retries-- > 0) {
                try {
                    $res = $client->get($pdfUrl, [
                        'auth' => [$authUser, $authPass]
                    ]);

                    if ($res->getStatusCode() === 200) {
                        $finalPdf = $res->getBody()->getContents();
                        return $finalPdf;
                    }
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    // Probably 404 — PDF not ready yet
                    Log::debug("PDF not ready yet: " . $e->getResponse()->getStatusCode());
                } catch (\Exception $e) {
                    Log::error("Unexpected error while polling PDF: " . $e->getMessage());
                    break;
                }

                sleep(2);
            }

            throw new \Exception("PDF not ready after timeout.");
        } catch (\Exception $e) {
            Log::error("PDF conversion API error: " . $e->getMessage());
            throw $e; // POCOR-9598

        } finally {
            // Cleanup
            if (file_exists($sheetPath)) {
                @unlink($sheetPath);
                Log::write('debug', "Deleted temp XLSX file: $sheetPath");
            }
        }

        return null;
    }

    // POCOR-9303
    private function printPdfViaLibreOffice(Spreadsheet $objSpreadsheet, string $baseFileName, ?string $studentId = null): ?string
    {

        $tempDir = TMP; // or "/tmp"
        $baseFileName = basename($baseFileName, '.xlsx'); // safe name, no path
//        putenv("HOME=$tempDir"); // Ensures LibreOffice has a writable HOME directory
//        Log::debug($tempDir);
        try {
            // 1. Save XLSX
            $xlsxPath = $tempDir . $baseFileName . '.xlsx';
            $pdfExpectedPath = $tempDir . $baseFileName . '.pdf';

            $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
            $objWriter->save($xlsxPath);

            $attributes = TableRegistry::getTableLocator()
                ->get('Configuration.ExternalDataSourceAttributes')
                ->find('list', ['keyField' => 'attribute_field', 'valueField' => 'value'])
                ->where(['external_data_source_type' => 'PDF Service'])
                ->disableHydration()
                ->toArray();
            $apiParams = $attributes['api_params'] ?? null;
            $convert_pdf = "pdf";
            $javaAvailable = false;
            exec("java -XshowSettings:properties -version 2>&1", $javaOutput, $javaCode);

            foreach ($javaOutput as $line) {
                if (stripos($line, 'java.runtime.name') !== false || stripos($line, 'Java(TM)') !== false) {
                    $javaAvailable = true;
                    break;
                }
            }

            if ($javaAvailable && $apiParams) {
                // Ensure proper JSON
                $apiParams = json_encode(json_decode($apiParams, true));

                if ($apiParams) {
                    $convert_pdf = 'pdf:calc_pdf_Export:' . $apiParams;
                }
            }
            // 2. Prepare command to run LibreOffice in headless mode
            $escapeTempDir = escapeshellarg($tempDir);
            $escapedSheet = escapeshellarg($xlsxPath);
            $escapedOutputDir = escapeshellarg($tempDir);

            $loCmd = "HOME=$escapeTempDir libreoffice --headless --convert-to $convert_pdf --outdir $escapedOutputDir $escapedSheet";

            // You may parse and apply $apiParams if needed
            // For example, if you want watermark, you may use unoconv with a custom template
            Log::debug("Running LibreOffice command: $loCmd");

            exec($loCmd, $output, $returnCode);
//            Log::debug("LibreOffice output: " . implode("\n", $output));
            if ($returnCode !== 0 || !file_exists($pdfExpectedPath)) {
                throw new \Exception("LibreOffice conversion failed with exit code $returnCode");
            }

            return file_get_contents($pdfExpectedPath);

        } catch (\Exception $e) {
            Log::error("LibreOffice PDF conversion error: " . $e->getMessage());
            throw $e; // POCOR-9598
        } finally {
            // 3. Cleanup
            if (file_exists($xlsxPath)) {
                @unlink($xlsxPath);
                Log::debug("Deleted XLSX: $xlsxPath");
            }

            if (file_exists($pdfExpectedPath)) {
                @unlink($pdfExpectedPath);
                Log::debug("Deleted PDF: $pdfExpectedPath");
            }
        }

        return null;
    }
    // POCOR-9336 end
}
?>
