<?php
namespace CustomExcel\Model\Behavior;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use DOMDocument;//POCOR-8529
use DOMElement; //POCOR-9052
use DOMXPath;//POCOR-8529
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup; // POCOR-9171
/*
    This trait is for ExcelReportBehavior.php
    To separate PDF logic
*/
trait PdfReportTrait
{
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

//    private function extractBorderStyle($headerString)
//    {
//      $styleList = [
//        'td' => []
//      ];
//      $maxValue = 9999;
//
//      for ($id = 0; $id < $maxValue; $id++) {
//        $targetCssStartTag = 'td.style';
//        $targetCssEndTag = 'th.style';
//
//        $targetCssStartTag .= $id;
//        $targetCssEndTag .= $id;
//
//        // Get the start tag position
//        $targetCssStartPos = strpos($headerString, $targetCssStartTag);
//
//        // Get the end tag position
//        $targetCssEndPos = strpos($headerString, $targetCssEndTag);
//
//        // Get the whole CSS style
//        $targetCss = substr($headerString, $targetCssStartPos, $targetCssEndPos - $targetCssStartPos);
//
//        if (empty($targetCss)) {
//            // When hit until the last Row ID it will stop extracting the border style
//            break;
//        } else {  // Extract all the style within this tag
//          $regexRemoveCssTag= preg_replace("/(".$targetCssStartTag." { )/", '', $targetCss);
//          $regexAddStyle= preg_replace("/( })/", '', $regexRemoveCssTag);
//
//          $styleList['td'][$id] = [
//            'style' => $regexAddStyle,
//            'hasBorder' => !$this->checkIfNoBorder($regexAddStyle)
//          ];
//        }
//      }
//      return $styleList;
//    }

    private function processHtml($htmlContent, $sheetIndex = 0): string
    {
        $tbodyStartTag = '<tbody>';
        $tbodyEndTag = '</tbody>';
        $tbodyStartLength = strlen($tbodyStartTag);

        // Locate the positions of the <tbody> section
        $startPos = strpos($htmlContent, $tbodyStartTag);
        $endPos = strpos($htmlContent, $tbodyEndTag);

        // Extract header, body, and footer segments
        $htmlHeader = substr($htmlContent, 0, $startPos + $tbodyStartLength);
        $htmlBody = substr($htmlContent, $startPos + $tbodyStartLength, $endPos - $startPos - $tbodyStartLength);
        $htmlFooter = substr($htmlContent, $endPos);

        // Step 1: Remove hidden rows/columns
        $cleanedBody = $this->removeColumnAndRow($htmlBody, $sheetIndex);

        // Step 2: Convert dotted borders to solid in the <style> section
        $htmlHeader = $this->styleBorderToSolid($htmlHeader);
        // POCOR-9210 start
        // Remove old <meta charset> if it exists
        $htmlHeader = preg_replace('/<meta\s+charset=["\']?[^"\'>]+["\']?\s*\/?>/i', '', $htmlHeader);

// Remove bad font-family rules
        $htmlHeader = preg_replace('/font-family\s*:\s*[^;"]+;?/i', '', $htmlHeader);

// Inject correct UTF-8 meta and good font
        $htmlHeader = preg_replace('/<head[^>]*>/i', '$0<meta charset="UTF-8">' .
            '<style>{ font-family: "Arial Unicode MS", "DejaVu Sans", sans-serif !important; }</style>', $htmlHeader);
        // POCOR-9210 end
        // Step 3: Normalize classes and inline styles including borders

        $processedBody = $this->processHtmlTable($cleanedBody, $htmlHeader);

        // Step 4: Remove page breaks that add an empty last page in PDF
        $updatedHeader = str_replace('page-break-after:always', '', $htmlHeader);

        // Combine everything back into the final processed HTML
        $finalHtml = $updatedHeader . $processedBody . $htmlFooter;
        return $finalHtml;
    }

    private function styleBorderToSolid($headString)
    {
        // To make the excel sheet to solid sheet
        $searchFormat = '.gridlines td { border:1px dotted black }';
        $replaceFormat = '.gridlines td'; // POCOR-7090 // initialy it was: $replaceFormat = '.gridlines td { border:1px solid black }';  //removed to avoid unnecessary border.
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

    private function extractClassStyleDefinitions(string $headHtml): array
    {
        $classStyles = [];

        // Match styles between td.styleX or th.styleX and the closing }
        // This regex supports multiline and optional whitespace
        preg_match_all('/(?:(?:td|th)\.style(\d+))\s*{\s*([^}]+)\s*}/i', $headHtml, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $id = $match[1];                // e.g., 78
            $styleDefinition = trim($match[2]); // e.g., border: 1px solid black; font-size: 8pt;

            $classStyles["style$id"] = $styleDefinition;
        }

        return $classStyles;
    }

    private function isBorderNone($css): bool
    {
        $positions = ['border-left:none', 'border-right:none', 'border-top:none', 'border-bottom:none'];
        foreach ($positions as $b) {
            if (stripos($css, $b) === false) {
                return false;
            }
        }
        return true;
    }
    private function normalizeCellBorderStyle(DOMElement $cell, array $styleList): void {
        $style = $cell->getAttribute('style');
        $style = strtolower($style); // Normalize case

        // Extract individual border styles
        $borders = [
            'border' => null,
            'border-top' => null,
            'border-left' => null,
            'border-right' => null,
            'border-bottom' => null,
        ];

        foreach (array_keys($borders) as $key) {
            if (preg_match('/' . preg_quote($key, '/') . '\s*:\s*([^;]+);?/', $style, $matches)) {
                $borders[$key] = trim($matches[1]);
            }
        }

        // Expand shorthand border to all sides if set
        if ($borders['border']) {
            foreach (['border-top', 'border-left', 'border-right', 'border-bottom'] as $side) {
                if (!$borders[$side]) {
                    $borders[$side] = $borders['border'];
                }
            }
        }

        // Normalize nulls to 'none' (assumed no border)
        foreach ($borders as &$value) {
            $value = $value ?? 'none';
        }

        // Now generate a standardized string from the border info
        $normalizedStyle = sprintf(
            'border-top:%s; border-left:%s; border-right:%s; border-bottom:%s;',
            $borders['border-top'],
            $borders['border-left'],
            $borders['border-right'],
            $borders['border-bottom']
        );

        // Try to find a matching style
        $matchedStyle = null;
        foreach ($styleList as $label => $def) {

            $normalizedDef = strtolower(str_replace(' ', '', trim($def)));
            $normalizedCurrent = strtolower(str_replace(' ', '', trim($normalizedStyle)));
//            Log::debug('ExcelReportBehavior >>> $label: '.$label . ' $def: '. print_r($def, true));
            if ($normalizedDef === $normalizedCurrent) {
                $matchedStyle = $def;
                break;
            }
        }

        // Apply the matched normalized style (if found), or leave original
        if ($matchedStyle) {
            $cell->setAttribute('style', $matchedStyle);
        } else {
            // fallback: only keep border part of original style
            $cell->setAttribute('style', $normalizedStyle);
        }
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

                    $rawText = "\xC2\xA0 " . trim($rawText) . " \xC2\xA0"; // POCOR-9171 start

                    if (mb_strlen($rawText) > 100) {
                        // Split long text into lines
                        $words = explode(' ', $rawText);
                        $lines = [];
                        $current = '';

                        foreach ($words as $word) {
                            if (mb_strlen($current . ' ' . $word) > 100) {
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
                    } else { // POCOR-9171 start
                        $cell->nodeValue = ''; // Clear original
                        $div = $dom->createElement('div');
                        $div->setAttribute('style', 'margin-left: 5px !important; margin-right: 5px !important;');
                        $div->appendChild($dom->createTextNode($rawText));
                        $cell->appendChild($div);
                    }
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

    /**
     * Normalizes a single style string: colors and borders.
     */
    /**
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

    private function normalizeTextWrappingStyles(string $style): string
    {
        $styles = [];

        // Parse incoming styles
        foreach (explode(';', $style) as $rule) {
            if (!trim($rule)) continue;
            [$key, $value] = array_map('trim', explode(':', $rule, 2) + [null, null]);
            if ($key && $value) {
                $styles[strtolower($key)] = $value;
            }
        }

        // Enforce wrapping styles
        $styles['white-space'] = 'normal';
        $styles['word-break'] = 'break-word';

        // Rebuild style string
        $final = '';
        foreach ($styles as $k => $v) {
            $final .= "$k: $v; ";
        }

        return trim($final);
    }
    private function neutralizeEmptyCells(DOMDocument $dom): void
    {
//        return;
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//td | //th') as $cell) {
            $text = trim($cell->textContent);
            if ( $text === ' ') {
                $cell->removeAttribute('style');
                $cell->setAttribute('style', 'border: none !important;');
            }

        }
    }

    public function processHtmlTable(string $html, string $headString): string
    {

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // POCOR-9210 start
        $utf8Wrapper = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="wrap">';
        $utf8Closer  = '</div></body></html>';
        $wrappedHtml = $utf8Wrapper . $html . $utf8Closer;
        $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        // POCOR-9210 end
        libxml_clear_errors();

        $this->inlineExcelStyles($dom, $headString);
//        $this->applyClassStylesToInline($dom, $styleList);
        $this->neutralizeEmptyCells($dom);

        return $dom->saveHTML();
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

    private function savePDF($objSpreadsheet, $filepath, $student_id, $report_card_id)
    {
        Log::write('debug', 'ExcelReportBehavior >>> filepath: '.$filepath);
        // Convert spreadsheet object into html
        $objSpreadsheet->getDefaultStyle()->getFont()->setName('Arial Unicode MS'); // POCOR-9210
        ob_start(); // POCOR-9210
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($objSpreadsheet);

        // This is to store to final processedHtml
        $processedHtml = '';
        $filePaths = [];
        $basePath = $filepath;
        //POCOR-6916 start
        $reportCard = TableRegistry::get('ReportCard.ReportCards');
        $configVal = $reportCard->find()->select(['pdf_no'=>$reportCard->aliasField('pdf_page_number')])->where([$reportCard->aliasField('id')=>$report_card_id])->first();
        if(!empty($configVal)){ //POCOR-7096
            $configValue =  $configVal['pdf_no'];
            if($configValue == -1){
                $sheetCount = $objSpreadsheet->getSheetCount();
            }else{
                $sheetCount = $configValue;
            }
        }else{
            $sheetCount = $objSpreadsheet->getSheetCount();
        }
        //POCOR-6916 end
        for ($sheetIndex = 0; $sheetIndex < $sheetCount; $sheetIndex++) {
            $sheetStatus = $objSpreadsheet->getSheet($sheetIndex)->getSheetState(); //POCOR-7077
            // POCOR-9171 start
            $pageSetup = $objSpreadsheet->getSheet($sheetIndex)->getPageSetup();

            $orientation = $pageSetup->getOrientation();
            $pageSizeP = [230, 350]; // A4 in mm
            $pageSizeL = [350, 230]; // A4 in mm
//            $fitToPage = $pageSetup->getFitToPage();     // true/false
//            $fitToWidth = $pageSetup->getFitToWidth();   // integer
//            $fitToHeight = $pageSetup->getFitToHeight(); // integer
//            if ($pageSetup->getPaperSize() === PageSetup::PAPERSIZE_A4) {
//                // If Excel used scaling (like 48%), adjust accordingly
//                $scale = $pageSetup->getScale(); // 48
//                if ($scale > 0 && $scale < 120) {
//                    $pageSizeXP = [
//                        round(210 * $scale / 100),
//                        round(297 * $scale / 100)
//                    ];
//                    $pageSizeXL = [
//                        round(297 * $scale / 100),
//                        round(210 * $scale / 100)
//                    ];
//                    Log::debug(print_r([$pageSizeXP, $pageSizeXL],true));
//                }
//            }

            if ($orientation === \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT) {
                $pageSize = $pageSizeP; // A4
                $orientation = 'P';
            } elseif ($orientation === \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE) {
                $pageSize = $pageSizeL;
                $orientation = 'L';
            }
            // POCOR-9171 end
            if ($sheetStatus === 'visible') { // POCOR-7077
                // Create new mPDF instance for the current sheet
                $pdf = new \Mpdf\Mpdf([
                    'mode' => 'UTF-8', // POCOR-9210
                    'format' => $pageSize // Custom landscape format (POCOR-7750)
                ]);
                $pdf->autoScriptToLang = true;  // Automatically select language-specific fonts (POCOR-7264)
                $pdf->autoLangToFont = true;    // Match language to font (POCOR-7264)

                $outputPathBase = $basePath . '_' . $sheetIndex;

                // Generate HTML from spreadsheet
                $writer->setSheetIndex($sheetIndex);
// POCOR-9210 start
                $writer->save('php://output');
                $rawHtml = ob_get_clean();
// POCOR-9210 end
//                $rawHtml = file_get_contents($outputPathBase, FILE_USE_INCLUDE_PATH);

                // Clean and filter HTML content
                $htmlCleaned = $this->processHtml($rawHtml, $sheetIndex);
                $hiddenCssClasses = $this->extractHiddenClasses($htmlCleaned);         // POCOR-8529
                $htmlFiltered = $this->removeHiddenElements($htmlCleaned, $hiddenCssClasses); // POCOR-8529

                // Debug logs for visual inspection
                $writeDebugPdf = function($html, $filename) use ($outputPathBase) {
                    $zdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [400, 245]]);
                    $zdf->SetDisplayMode('real');
                    $zdf->AddPage('L');
                    $zdf->WriteHTML($html);
                    $zdf->Output(LOGS . $filename, 'F');
                    unset($zdf);
                };
// Write debug HTML files
//                file_put_contents(LOGS . 'debug_before_cleaning.html', $rawHtml);
//                file_put_contents(LOGS . 'debug_after_cleaning.html', $htmlCleaned);
//                file_put_contents(LOGS . 'debug_after_filtering.html', $htmlFiltered);

// Write debug PDF files
//                $writeDebugPdf($rawHtml, 'debug_before_cleaning.pdf');
//                $writeDebugPdf($htmlCleaned, 'debug_after_cleaning.pdf');
//                $writeDebugPdf($htmlFiltered, 'debug_after_filtering.pdf');

                // Generate PDF
                $pdf->SetFontSize(1);
                $pdf->SetFont('sans-serif');
                $pdf->SetDisplayMode('fullpage');
                $pdf->AddPage('L');
                $pdf->WriteHTML($htmlFiltered);

                $finalPdfPath = $outputPathBase . '.pdf';
                $pdf->Output($finalPdfPath, 'F');

                $filePaths[] = $finalPdfPath;

                unset($pdf);
            }
        }
        // Merge all the pdf that belongs to one report
        if(!empty($student_id)) {
            $fileName = $this->getConfig('filename') . '_' . $student_id;
        } else {
            $fileName = $this->getConfig('filename') . '_' . date('Ymd') . 'T' . date('His');
        }

        Log::write('debug', '----------------------fileName---------------------: ');
        Log::write('debug', $fileName);

        $this->mergePDFFiles($filePaths, $fileName, $fileName);
        // // Remove the temp file that is converted from excel object and its successfully converted to pdf
        if ($this->getConfig('purge')) {
            foreach ($filePaths as $filepath) {
                // delete excel file after successfully converted to pdf
                $this->deleteFile($filepath);
            }
        }
    }

    /**
    * POCOR-6908
    */
    private function savePDFAssessment($objSpreadsheet, $filepath, $student_id,$paramVal)
    {

        Log::write('debug', 'ExcelReportBehavior >>> filepath: '.$filepath);
        // Convert spreadsheet object into html
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($objSpreadsheet);

        // This is to store to final processedHtml
        $processedHtml = '';
        $filePaths = [];
        $basePath = $filepath;
        for ($sheetIndex = 0; $sheetIndex < $objSpreadsheet->getSheetCount(); $sheetIndex++) {
            $mpdf = new \Mpdf\Mpdf(array('', '', 0, '', 15, 15, 16, 16, 9, 9, 'P')); //POCOR-6916
            $mpdf->autoScriptToLang = true; //POCOR-7264
            $mpdf->autoLangToFont = true; //POCOR-7264
            $filepath = $basePath.'_'.$sheetIndex;
            $prefixName = 'AssessmentResults';
            $date =  date("Ymd:HHmmss");
            $namePdf = $prefixName.'_'.$date;
            $writer->setSheetIndex($sheetIndex);
            $writer->save($filepath);

            // Read the html file and convert them into a variable
            $file = file_get_contents($filepath, FILE_USE_INCLUDE_PATH);

            // Remove all the redundant rows and columns
            $processedHtml = $this->processHtml($file, $sheetIndex);

            // Save the processed html into a temp pdf
            $mpdf->AddPage('L');

            $mpdf->WriteHTML($processedHtml);
            $filepathname = $namePdf.'.pdf';
            $mpdf->Output($filepathname,'D');
            $filePaths[] = $filepath;
            unset($mdpf);// POCOR-6908 end
        }
        // Merge all the pdf that belongs to one report
        if(!empty($student_id)) {
            $fileName = $this->getCconfig('filename') . '_' . $student_id;
        } else {
            $fileName = $this->getConfig('filename') . '_' . date('Ymd') . 'T' . date('His');
        }

        Log::write('debug', '----------------------fileName---------------------: ');
        Log::write('debug', $fileName);

        // $this->mergePDFFiles($filePaths, $fileName, $fileName); //V4
        $this->mergePDFFilesAssessment($filePaths, $fileName, $fileName);
        // // Remove the temp file that is converted from excel object and its successfully converted to pdf
        if ($this->getConfig('purge')) {
            foreach ($filePaths as $filepath) {
                // delete excel file after successfully converted to pdf
                $this->deleteFile($filepath);
            }
        }
    }

    private function mergePDFFilesAssessment(Array $filenames, $outFile, $title = '', $author = '', $subject = '')
    {
       // $mpdf = new \Mpdf\Mpdf(array('utf-8', '', 0, '', 15, 15, 16, 16, 9, 9, 'P')); //POCOR-6916
       $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [400, 220]]); //POCOR-7090
       $mpdf->SetTitle($title);
       $mpdf->SetAuthor($author);
       $mpdf->SetSubject($subject);
       $mpdf->autoScriptToLang = true; //POCOR-7264
       $mpdf->autoLangToFont = true; //POCOR-7264
       if ($filenames) {
//           echo "<pre>";print_r($mpdf);die; //This is very unusual code need to debug again
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
                           $mpdf->SetFontSize(1);
                           $mpdf->SetDisplayMode('fullpage');
                           $mpdf->AddPage('L');

                           $mpdf->UseTemplate ($tplId);
                       }
                       else {
                           $mpdf->state = 1;
                           $mpdf->SetFontSize(1);
                           $mpdf->SetDisplayMode('fullpage');
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
    }

    private function mergePDFFiles(Array $filenames, $outFile, $title = '', $author = '', $subject = '')
    {
        // $mpdf = new \Mpdf\Mpdf(array('utf-8', '', 0, '', 15, 15, 16, 16, 9, 9, 'P')); //POCOR-6916
        //$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [400, 220]]); //POCOR-7090
                $tmpdf = new \Mpdf\Mpdf(['mode' => 'utf-8']); //POCOR-8961
        $width = 297;
        $height = 210;
        if ($filenames) {
            if (isset($filenames[0])) {
                $curFile = $filenames[0];
                if (file_exists($curFile)) {
                    $tmpdf->SetSourceFile($curFile);
                    $tplId = $tmpdf->ImportPage(1);
                    $wh = $tmpdf->getTemplateSize($tplId);
                    $orientation = trim($wh['orientation']) ?? 'L';
                    $width = $wh['width'] ?? 297;
                    $height = $wh['height'] ?? 210;
                }
            }
        }
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8',
            'format' => [$width,$height],
//            'margin_left' => 40,
//            'margin_right' => 10,
//            'margin_top' => 30,
//            'margin_bottom' => 30,
        ]); //POCOR-8961
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor($author);
        $mpdf->SetSubject($subject);
        $mpdf->autoScriptToLang = true; //POCOR-7264
        $mpdf->autoLangToFont = true; //POCOR-7264
        if ($filenames) {
            $filesTotal = sizeof($filenames);
            // $mpdf->SetImportUse();

            for ($i = 0; $i<count($filenames);$i++) {
                $curFile = $filenames[$i];
                if (file_exists($curFile)){
                    $pageCount = $mpdf->SetSourceFile($curFile);
                    for ($p = 1; $p <= $pageCount; $p++) {
                        $tplId = $mpdf->ImportPage($p);
                        $tplSize = $mpdf->getTemplateSize($tplId);
//                        Log::debug(print_r($wh,true));
                        $orientation = trim($wh['orientation']) ?? 'L';
                        $tplWidth = $tplSize['width'];
                        $tplHeight = $tplSize['height'];
                        $pageWidth = $mpdf->w;
                        $pageHeight = $mpdf->h;

// Calculate center offsets
                        $offsetX = ($pageWidth - $tplWidth) / 2;
                        $offsetY = ($pageHeight - $tplHeight) / 2;

// Add page with exact size if needed

                        if (($p==1)){
                            $mpdf->state = 0;
                            $mpdf->SetFontSize(1);
                            $mpdf->SetDisplayMode('fullpage');
                            $mpdf->AddPage($tplSize['orientation'], '', '', '', '', '', '', '', '', '', '', [$tplWidth, $tplHeight]);
                            $mpdf->UseTemplate($tplId, $offsetX, $offsetY);
                        }
                        else {
                            $mpdf->state = 1;
                            $mpdf->SetFontSize(1);
                            $mpdf->SetDisplayMode('fullpage');
                            $mpdf->AddPage($tplSize['orientation'], '', '', '', '', '', '', '', '', '', '', [$tplWidth, $tplHeight]);
                            $mpdf->UseTemplate($tplId, $offsetX, $offsetY);
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
    }
    //POCOR-8529 start(to remove hidden columns from pdf)
    function extractHiddenClasses($html) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // POCOR-9210 start
        $utf8Header = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        $html = $utf8Header . $html;
        // POCOR-9210 end
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Extract CSS from <style> tags
        $styles = '';
        $styleTags = $dom->getElementsByTagName('style');
        foreach ($styleTags as $styleTag) {
            $styles .= $styleTag->nodeValue;
        }

        // Regular expression to find CSS rules with display: none
        $pattern = '/table\.(.*?)\s+\.column(\d+)\s*\{[^}]*display\s*:\s*none\s*;?\s*\}/i';
        preg_match_all($pattern, $styles, $matches, PREG_SET_ORDER);

        $hiddenClasses = [];
        foreach ($matches as $match) {
            $sheetNumber = $match[1];
            $columnNumber = $match[2];
            $hiddenClasses[] = "table.{$sheetNumber} .column{$columnNumber}";
        }

        return $hiddenClasses;
    }
    function removeHiddenElements($html, $hiddenClasses) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        // POCOR-9210 start
        $utf8Header = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        $html = $utf8Header . $html;
        // POCOR-9210 end
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        foreach ($hiddenClasses as $classSelector) {
            // Convert CSS class selector to XPath
            $xpathQuery = $this->convertCssSelectorToXpath($classSelector);

            // Find and remove elements
            $elements = $xpath->query($xpathQuery);
            foreach ($elements as $element) {
                $element->parentNode->removeChild($element);
            }
        }

        return $dom->saveHTML();
    }

    function convertCssSelectorToXpath($selector) {
        // Split the selector into parts (by spaces)
        $parts = explode(' ', $selector);
        $xpathParts = [];

        foreach ($parts as $part) {
            if (strpos($part, '.') !== false) {
                // Handle classes
                $element = '*';
                if (strpos($part, '.') !== 0) {
                    list($element, $class) = explode('.', $part, 2);
                } else {
                    $class = ltrim($part, '.');
                }
                $classes = explode('.', $class);
                $xpathCondition = $element;
                foreach ($classes as $cls) {
                    $xpathCondition .= "[contains(concat(' ', normalize-space(@class), ' '), ' $cls ')]";
                }
                $xpathParts[] = $xpathCondition;
            } elseif (strpos($part, '#') !== false) {
                // Handle IDs if necessary
            } else {
                // Handle element names (e.g., 'table')
                $xpathParts[] = $part;
            }
        }

        // Combine into full XPath
        return '//' . implode('//', $xpathParts);
    }
    // POCOR-8529  end
}

?>
