<?php
namespace CustomExcel\Model\Behavior;

use Cake\Log\Log;
use Mpdf\MpdfException;

// POCOR-9153
use DOMDocument;

// POCOR-9153
use DOMXPath;
use DOMElement;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Cake\ORM\TableRegistry;

// POCOR-9153

/*
    This trait is for ExcelReportBehavior.php
    To separate PDF logic
*/

trait StudentPdfReportTrait
{
    const PRINTER_MPDF = 1;
    const PRINTER_LIBREOFFICE = 2;
    const PRINTER_EXTERNAL = 3;
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
        for ($i = strlen($targetColumnValue) - 1; $i >= 0; $i--) {
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
        if (isset($this->excelLastRowValueArr[$this->currentWorksheetIndex]) && $targetRowValue < $this->excelLastRowValueArr[$this->currentWorksheetIndex]) {
            return;
        }
        $this->excelLastRowValueArr[$this->currentWorksheetIndex] = $targetRowValue;
    }


    /**
     * Export PDF using either API or mPDF depending on config.
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function savePDF($objSpreadsheet, $baseFilePath, $studentId)
    {
        Log::write('debug', 'ExcelReportBehavior >>> base filepath: ' . $baseFilePath);

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $printer = $ConfigItems->value('pdf_service');
        switch ($printer) {
            case self::PRINTER_MPDF:
                $pdfContent = $this->printPdfViaMpdf($objSpreadsheet, $baseFilePath, $studentId);
                break;
            case self::PRINTER_LIBREOFFICE:
                $pdfContent = $this->printPdfViaLibreOffice($objSpreadsheet, $baseFilePath, $studentId);
                break;
            case self::PRINTER_EXTERNAL:
                $pdfContent = $this->printPdfViaApi($objSpreadsheet, $baseFilePath . '_sheet' . $studentId);
                break;
        }

        if (!empty($pdfContent)) {
            $filename = $this->getConfig('filename') . '_' . (!empty($studentId) ? $studentId : date('Ymd\THis')) . '.txt';
            $outputPath = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS . $filename;
            file_put_contents($outputPath, $pdfContent);
            Log::write('debug', "Saved PDF  to: $outputPath");
        } else {
            Log::error("PDF content  is empty");
        }
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
            return null;
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

    /**
     * Generate PDF using mPDF from given Spreadsheet
     * Returns raw merged PDF bytes.
     *
     * @param Spreadsheet $objSpreadsheet
     * @param string $baseFilePath
     * @param mixed $studentId
     * @return ?string
     * @throws \Mpdf\MpdfException
     */
    private function printPdfViaMpdf($objSpreadsheet, $baseFilePath, $studentId): ?string
    {
        Log::write('debug', 'ExcelReportBehavior >>> base filepath: ' . $baseFilePath);
        $sheetCount = $objSpreadsheet->getSheetCount();
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($objSpreadsheet);

        $tempFiles = [];
        $pdfPaths = [];

        for ($sheetIndex = 0; $sheetIndex < $sheetCount; $sheetIndex++) {
            $sheet = $objSpreadsheet->getSheet($sheetIndex);
            if ($sheet->getSheetState() !== 'visible') {
                continue;
            }

            // Track worksheet
            if ($this->currentWorksheet !== $sheet) {
                $this->currentWorksheetIndex++;
                $this->currentWorksheet = $sheet;
            }

            $orientation = $sheet->getPageSetup()->getOrientation();
            $isLandscape = $orientation === \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE;
            $mpdfOrientation = $isLandscape ? 'L' : 'P';
            $mpdfFormat = $isLandscape ? 'A4-L' : 'A4';

            // Define paths
            $sheetBasePath = $baseFilePath . '_sheet' . $sheetIndex;
            $xlsPath = $sheetBasePath . '.xlsx';
            $htmlRawPath = $sheetBasePath . '_raw.html';
            $htmlProcessedPath = $sheetBasePath . '_processed.html';
            $pdfProcessedPath = $sheetBasePath . '.pdf';

            // Save XLSX
            $writer->setSheetIndex($sheetIndex);
            $writer->save($xlsPath);
            $tempFiles[] = $xlsPath;

            // Generate raw HTML (simulated for future compatibility)
            $rawHtml = file_get_contents($xlsPath, FILE_USE_INCLUDE_PATH);
            file_put_contents($htmlRawPath, $rawHtml);
            $tempFiles[] = $htmlRawPath;

            // Process HTML
            $processedHtml = $this->processHtml($rawHtml);
            file_put_contents($htmlProcessedPath, $processedHtml);
            $tempFiles[] = $htmlProcessedPath;

            // Render to PDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => $mpdfFormat,
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            $mpdf->autoMarginPadding = true;
            $mpdf->autoPageBreak = true;

            $mpdf->AddPage($mpdfOrientation);
            $mpdf->WriteHTML($processedHtml);
            $mpdf->Output($pdfProcessedPath, 'F');

            $pdfPaths[] = $pdfProcessedPath;
            $tempFiles[] = $pdfProcessedPath;

            Log::write('debug', "Saved XLSX: $xlsPath");
            Log::write('debug', "Saved RAW HTML: $htmlRawPath");
            Log::write('debug', "Saved PROCESSED HTML: $htmlProcessedPath");
            Log::write('debug', "Saved FINAL PDF: $pdfProcessedPath");
        }

        // Merge all sheet-level PDFs
        $filename = $this->getConfig('filename') . '_' . (!empty($studentId) ? $studentId : date('Ymd\THis'));
        Log::write('debug', 'Merging PDF files under name: ' . $filename);

        $mergedPdf = $this->mergePDFFiles($pdfPaths, $filename, $filename);

        // Clean up temp files
        if ($this->getConfig('purge')) {
            foreach ($tempFiles as $file) {
                $this->deleteFile($file);
            }
        }

        return $mergedPdf;
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

        $processedString = $this->processHtmlTable($processedString, $processedHeadString); // POCOR-9153


        // To remove empty page at the end of the pdf
        $searchFormat = 'page-break-after:always';
        $processedHeadString = str_replace($searchFormat, '', $processedHeadString);

        // Combined all the processed Head, Body, Tail html into one
        $processedHtml = $processedHeadString . $processedString . $tailString;
        return $processedHtml;
    }

    private function removeColumnAndRow($processingString, $sheetIndex)
    {
        $processedHtmlRows = [];
        $targetRowValue = $this->excelLastRowValueArr[$sheetIndex + 1];

        // Loop from 0 to LastRow to remove column (Row by Row)
        for ($id = 0; $id < $targetRowValue; $id++) {
            $targetRowString = '<tr class="row' . $id . '">';
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
            //POCOR-7747 start
            if ($this->lastColumn == 0) {
                $this->lastColumn = 26;//set to maximum column if lastColumn is empty to generate all report cards of any template
            }
            //POCOR-7747 end
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
        for ($id = 0; $id < count($processedHtmlRows); $id++) {
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

        while (true) {
            $next += $increment;

            if ($next + $increment > $to) {
                if ($next <= $to) {
                    $ranges[] = $next;
                }
                $increment /= 10;
                $higher = false;
            } elseif ($next % ($increment * 10) === 0) {
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

            for ($j = 0; $j < strlen($str_from); $j++) {
                if ($str_from[$j] == $str_to[$j]) {
                    $regex .= $str_from[$j];
                } else {
                    $regex .= "[" . $str_from[$j] . "-" . $str_to[$j] . "]";
                }
            }
            $regex .= "|";
        }

        return substr($regex, 0, strlen($regex) - 1) . ')';
    }

    /**
     * // POCOR-9153
     * Normalizes a single style string: colors and borders.
     */

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
                $regexRemoveCssTag = preg_replace("/(" . $targetCssStartTag . " { )/", '', $targetCss);
                $regexAddStyle = preg_replace("/( })/", '', $regexRemoveCssTag);

                $styleList['td'][$id] = [
                    'style' => $regexAddStyle,
                    'hasBorder' => !$this->checkIfNoBorder($regexAddStyle)
                ];
            }
        }
        return $styleList;
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
        $replaceFormat = '<style> td { padding-left: 5px !important padding-right: 5px !important; }';
        $headString = str_replace($searchFormat, $replaceFormat, $headString);

        return $headString;
    }

    //  ================ START REMOVE COLUMN AND ROW ================

    public function processHtmlTable(string $html, string $headString): string
    {

        libxml_use_internal_errors(true);
        // POCOR-9153 start
        $utf8Wrapper = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<div id="wrap">
<table>
HTML;

        $utf8Closer = <<<HTML
</table>
</div>
</body>
</html>
HTML;

        $wrappedHtml = $utf8Wrapper . $html . $utf8Closer;

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        // POCOR-9153 end

        // Set table-wide defaults

        $this->inlineExcelStyles($dom, $headString);
//        $this->applyClassStylesToInline($dom, $styleList);
//        $this->neutralizeEmptyCells($dom);
        // POCOR-9153 start
        $body = $dom->getElementById('wrap');
        $html = '';
        foreach ($body->getElementsByTagName('table')->item(0)->childNodes as $node) {
            $html .= $dom->saveHTML($node);
        }
        return $html;
        // POCOR-9153 end
    }

    /**
     * Parses and applies Excel-style class styles inline, then removes the class attribute.
     */
    private function inlineExcelStyles(DOMDocument $dom, string $headString): void
    {
        $styleList = $this->extractAndNormalizeClassStyles($headString);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//td | //th') as $cell) {
            if (!$cell->hasAttribute('class')) {
                continue;
            }

            $classes = explode(' ', $cell->getAttribute('class'));
            $inlineStyles = [];

            foreach ($classes as $class) {
                if (isset($styleList[$class])) {
                    $inlineStyles[] = $styleList[$class];
                }
            }

            if ($inlineStyles) {
                $merged = implode('; ', $inlineStyles);
                $existing = $cell->getAttribute('style');

                // Parse styles into associative array
                $styles = [];

                // Add new inline styles
                foreach (explode(';', $merged) as $style) {
                    if (strpos($style, ':') !== false) {
                        [$key, $value] = array_map('trim', explode(':', $style, 2));
                        $styles[strtolower($key)] = $value;
                    }
                }

                // Add/override with existing styles
                foreach (explode(';', $existing) as $style) {
                    if (strpos($style, ':') !== false) {
                        [$key, $value] = array_map('trim', explode(':', $style, 2));
                        $styles[strtolower($key)] = $value;
                    }
                }

                // Always enforce padding last
                $styles['padding'] = '5px !important';

                // Rebuild style string
                $finalStyle = '';
                foreach ($styles as $key => $value) {
                    $finalStyle .= "$key: $value; ";
                }
                $normalized = $this->normalizeBorderStylesOnly($finalStyle);
//                $normalized = $this->normalizeTextWrappingStyles($normalized);
                if ($cell->childNodes->length === 1 && $cell->firstChild->nodeType === XML_TEXT_NODE) {
                    $rawText = trim($cell->textContent);

                    if (mb_strlen($rawText) > 35) {
                        // Split long text into lines
                        $words = explode(' ', $rawText);
                        $lines = [];
                        $current = '';

                        foreach ($words as $word) {
                            if (mb_strlen($current . ' ' . $word) > 35) {
                                $lines[] = $current;
                                $current = $word;
                            } else {
                                $current .= ($current === '' ? '' : ' ') . $word;
                            }
                        }
                        if ($current !== '') {
                            $lines[] = $current;
                        }

                        // Replace content with text + <br> tags
                        $cell->nodeValue = ''; // Clear original

                        foreach ($lines as $i => $line) {
                            $cell->appendChild($dom->createTextNode($line));
                            if ($i < count($lines) - 1) {
                                $cell->appendChild($dom->createElement('br'));
                            }
                        }

                        // Optional: enforce wrapping
                        $cell->setAttribute(
                            'style',
                            $cell->getAttribute('style') . '; white-space: normal; word-break: break-word;'
                        );
                    }
                }

// Wrap string-only cells in a <div> with margin
                if (
                    $cell->childNodes->length === 1 &&
                    $cell->firstChild->nodeType === XML_TEXT_NODE &&
                    trim($cell->textContent) !== ''
                ) {
                    $text = "\xC2\xA0 " . trim($cell->textContent) . " \xC2\xA0";

                    $cell->nodeValue = ''; // Clear original

                    $div = $dom->createElement('div');
                    $div->setAttribute('style', 'margin-left: 5px !important; margin-right: 5px !important;');
                    $div->appendChild($dom->createTextNode($text));

                    $cell->appendChild($div);
                }
                $cell->setAttribute('style', trim($normalized));
            }

            // Remove class
            $cell->removeAttribute('class');
        }
    }

    /**
     * Extracts td/th.class styles and normalizes them.
     */
    private function extractAndNormalizeClassStyles(string $css): array
    {
        $styles = [];

        preg_match_all('/(td|th)\.(style\d+)\s*\{([^}]+)}/i', $css, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $class = $match[2];
            $rawStyle = trim($match[3]);
            $cleanedWhiteSpaces = preg_replace('/\s+/', ' ', $rawStyle); // Normalize whitespace
            $styles[$class] = $cleanedWhiteSpaces;
        }

        return $styles;
    }
    //  ================ END REMOVE COLUMN AND ROW ================

    /**
     * // POCOR-9153
     * Normalize inline CSS: deduplicate, reorder, and collapse borders.
     */
    function normalizeBorderStylesOnly(string $style): string
    {
        // Prepare patterns
        $borders = ['top', 'right', 'bottom', 'left'];
        $normalized = [];
        $otherStyles = [];

        // Parse
        foreach (explode(';', $style) as $rule) {
            if (!trim($rule)) continue;
            [$key, $value] = array_map('trim', explode(':', $rule, 2) + [null, null]);

            if (!$key || !$value) continue;

            // Normalize colors
            $value = preg_replace('/#?ffffff/i', 'white', $value);
            $value = preg_replace('/#?000000/i', 'black', $value);

            // Normalize borders
            if ($key === 'border') {
                // full border style
                if (stripos($value, 'none') !== false || stripos($value, 'white') !== false) {
                    $normalized['border'] = 'white';
                } else {
                    $normalized['border'] = 'solid 1px black';
                }
            } elseif (preg_match('/^border\-(top|right|bottom|left)$/', $key, $matches)) {
                $side = $matches[1];
                if (stripos($value, 'none') !== false || stripos($value, 'white') !== false) {
                    $normalized["border-$side"] = 'white';
                } else {
                    $normalized["border-$side"] = 'solid 1px black';
                }
            } else {
                $otherStyles[] = "$key: $value";
            }
        }

        // Collapse borders if all are same
        $sideVals = array_map(fn($s) => $normalized["border-$s"] ?? null, $borders);
        if (count(array_unique($sideVals)) === 1 && $sideVals[0] !== null) {
            $normalized = ['border' => $sideVals[0]];
            foreach ($borders as $s) unset($normalized["border-$s"]);
        }

        // Merge final
        $merged = [];
        foreach (array_merge($normalized, []) as $k => $v) {
            $merged[] = "$k: $v";
        }
        return implode('; ', array_merge($merged, $otherStyles));
    }

    /*private function mergePDFFiles(Array $filenames, $outFile, $title = '', $author = '', $subject = '')
    {
        $mpdf = new \Mpdf\Mpdf();
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor($author);
        $mpdf->SetSubject($subject);

        if ($filenames) {
            $filesTotal = sizeof($filenames);
            $mpdf->SetImportUse();

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
        $pdf_file_path = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS;
        $content = $mpdf->Output($file_path, "S");
        $fp = fopen($pdf_file_path . $outFile . ".txt","wb");
        fwrite($fp,$content);
        fclose($fp);
        unset($mpdf);
    }*/

    /**
     * Merge multiple PDF files into one, preserving page sizes and centering each imported page
     *
     * @param array $filenames List of input PDF paths
     * @param string $outFile Base name for the output PDF (without “.pdf”)
     * @param string $title (optional) PDF document title
     * @param string $author (optional) PDF document author
     * @param string $subject (optional) PDF document subject
     * @return ?string   returns merged raw PDF
     */
    private function mergePDFFiles(array $filenames, string $outFile, string $title = '', string $author = '', string $subject = ''): ?string
    {
        // If no files, nothing to do
        if (empty($filenames)) {
            return null;
        }

        // Step 1: Probe the first file to get default orientation & size
        $probe = new \Mpdf\Mpdf(['mode' => 'utf-8']);
        $firstFile = $filenames[0];
        if (!file_exists($firstFile)) {
            throw new \RuntimeException("Cannot find PDF to merge: {$firstFile}");
        }
        $probe->SetSourceFile($firstFile);
        $tplId = $probe->ImportPage(1);
        $tplSize = $probe->getTemplateSize($tplId);
        $defaultOrientation = $tplSize['orientation'] ?? 'P';
        $defaultWidth = $tplSize['width'] ?? 210;
        $defaultHeight = $tplSize['height'] ?? 297;
        unset($probe);

        // Step 2: Create main Mpdf with 10-pt margins
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [$defaultWidth, $defaultHeight],
            'orientation' => $defaultOrientation,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor($author);
        $mpdf->SetSubject($subject);
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        // Step 3: Loop through each file & import all pages
        foreach ($filenames as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }

            // Load and import all pages in this file
            $pageCount = $mpdf->SetSourceFile($filePath);
            for ($p = 1; $p <= $pageCount; $p++) {
                $tplId = $mpdf->ImportPage($p);
                $size = $mpdf->getTemplateSize($tplId);
                $ori = $size['orientation'];
                $tplW = $size['width'];
                $tplH = $size['height'];

                // Add a new page matching the imported page’s size & orientation
                $mpdf->AddPage(
                    $ori,
                    '', '', '', '',   // use default header/footer settings
                    20, 20, 20, 20,   // left, right, top, bottom margins
                    0, 0,             // margin_header, margin_footer
                    [$tplW, $tplH]
                );

                // Compute inner printable area
                $innerW = $mpdf->w - $mpdf->lMargin - $mpdf->rMargin;
                $innerH = $mpdf->h - $mpdf->tMargin - $mpdf->bMargin;

                // Center the template within margins
                $offsetX = $mpdf->lMargin + (($innerW - $tplW) / 2);
                $offsetY = $mpdf->tMargin + (($innerH - $tplH) / 2);

                // Place the imported page
                $mpdf->UseTemplate($tplId, null, null, null, null, true); // POCOR-9292
            }
        }

        // Step 4: Write out merged PDF
        $outputPath = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS . $outFile . '.pdf';
        $rawPdfContent = $mpdf->Output($outputPath, \Mpdf\Output\Destination::STRING_RETURN);

//        // Also dump raw PDF bytes to .txt for debugging
//        $txtPath = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS . $outFile . '.txt';
//        file_put_contents($txtPath, $rawPdfContent);

        unset($mpdf);
        return $rawPdfContent;
    }


}

?>
