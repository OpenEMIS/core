<?php
namespace CustomExcel\Model\Behavior;

use Cake\Log\Log;
use Mpdf\MpdfException;
use DOMDocument;
use DOMXPath;

/*
    This trait is for ExcelReportBehavior.php
    To separate PDF logic
*/

trait StudentPdfReportTrait
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
     * @throws MpdfException
     */
    private function savePDF($objSpreadsheet, $filepath, $student_id)
    {
        Log::write('debug', 'ExcelReportBehavior >>> filepath: ' . $filepath);
        // Convert spreadsheet object into html
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($objSpreadsheet);

        // This is to store to final processedHtml
        $processedHtml = '';
        $filePaths = [];
        $basePath = $filepath;
        for ($sheetIndex = 0; $sheetIndex < $objSpreadsheet->getSheetCount(); $sheetIndex++) {
            $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [410, 280]]); //POCOR-8961
            $mpdf->autoScriptToLang = true; //POCOR-7264
            $mpdf->autoLangToFont = true; //POCOR-7264
            $filepath = $basePath . '_' . $sheetIndex;
            $writer->setSheetIndex($sheetIndex);
            $writer->save($filepath);

            // Read the html file and convert them into a variable
            $file = file_get_contents($filepath, FILE_USE_INCLUDE_PATH);

            // Remove all the redundant rows and columns
            $processedHtml = $this->processHtml($file, $sheetIndex);
//            file_put_contents(LOGS . 'debug_after_cleaning_' . $sheetIndex . '.html', $processedHtml);


            // Save the processed html into a temp pdf
            $mpdf->AddPage('L');

            $mpdf->WriteHTML($processedHtml);
            $filepath = $filepath . '.pdf';

            $mpdf->Output($filepath, 'F');
            $filePaths[] = $filepath;
            unset($mdpf);
        }
        // Merge all the pdf that belongs to one report
        if (!empty($student_id)) {
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

        $processedString = $this->processHtmlTable($processedString, $processedHeadString);


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

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        // Set table-wide defaults

        $this->inlineExcelStyles($dom, $headString);
//        $this->applyClassStylesToInline($dom, $styleList);
//        $this->neutralizeEmptyCells($dom);

        return $dom->saveHTML();
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
                    }
                }

// Wrap string-only cells in a <div> with margin
                if (
                    $cell->childNodes->length === 1 &&
                    $cell->firstChild->nodeType === XML_TEXT_NODE &&
                    trim($cell->textContent) !== ''
                ) {
                    $text = trim($cell->textContent);
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

    // POCOR-8298

    private function mergePDFFiles(array $filenames, $outFile, $title = '', $author = '', $subject = '')
    {
        // Create a new Mpdf instance
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [410, 280]]); //POCOR-8961
        $mpdf->autoScriptToLang = true; //POCOR-7264
        $mpdf->autoLangToFont = true; //POCOR-7264
        $mpdf->SetTitle($title);
        $mpdf->SetAuthor($author);
        $mpdf->SetSubject($subject);

        // Loop through each file and import its pages
        foreach ($filenames as $curFile) {
            if (file_exists($curFile)) {
                $pageCount = $mpdf->SetSourceFile($curFile);
                for ($p = 1; $p <= $pageCount; $p++) {
                    $tplId = $mpdf->ImportPage($p);
                    $wh = $mpdf->getTemplateSize($tplId, 410, 280);
//                    Log::debug(print_r($wh,true));
                    $orientation = ($wh['width'] > $wh['height']) ? 'L' : 'P';
                    $mpdf->AddPage($orientation);
                    $mpdf->UseTemplate($tplId);
                    // Apply CSS styling for font size and right border
                    $mpdf->WriteHTML('<div style="font-size: 10pt; border-right: 1px solid black;"></div>', \Mpdf\HTMLParserMode::HTML_BODY);
                }
            }
        }

        // Define file paths
        $file_path = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS . $outFile . '.pdf';
        $pdf_file_path = WWW_ROOT . $this->getConfig('folder') . DS . $this->getConfig('subfolder') . DS;

        // Output the merged PDF to the specified file
        $content = $mpdf->Output($file_path, "S");

        // Save the PDF content to a text file
        $fp = fopen($pdf_file_path . $outFile . ".txt", "wb");
        fwrite($fp, $content);
        fclose($fp);

        // Clean up
        unset($mpdf);
    }


}

?>
