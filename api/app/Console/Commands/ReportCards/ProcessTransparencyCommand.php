<?php

namespace App\Console\Commands\ReportCards;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ProcessTransparencyCommand extends Command
{
    protected $signature = 'reportcards:fix-transparency {filePath : Absolute path to xlsx file}';

    protected $description = 'Bake GD transparency into embedded drawing images in an xlsx report card template';

    public function handle(): int
    {
        $filePath = $this->argument('filePath');

        //$this->info("Attempting to open file: {$filePath}");

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
            //$this->info("Successfully loaded spreadsheet: {$filePath}");
        } catch (\Exception $e) {
            $this->error("Failed to load spreadsheet: " . $e->getMessage());
            return self::FAILURE;
        }

        $totalDrawings = 0;
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $totalDrawings += count($sheet->getDrawingCollection());
        }
        //$this->info("Total drawings found in spreadsheet: {$totalDrawings}");

        $modified = false;
        $drawingIndex = 0;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetTitle = $sheet->getTitle();
            foreach ($sheet->getDrawingCollection() as $drawing) {
                $drawingIndex++;
                $this->comment("--- Processing Drawing #{$drawingIndex} in sheet '{$sheetTitle}' ---");

                if (!($drawing instanceof Drawing)) {
                    $this->warn("Drawing #{$drawingIndex} is not an instance of PhpOffice\\PhpSpreadsheet\\Worksheet\\Drawing. Skipping.");
                    continue;
                }

                // getOpacity() available since PhpSpreadsheet 3.4.0
                // Returns 0–100000 where 100000 = fully opaque, 0 = fully transparent
                $opacity = method_exists($drawing, 'getOpacity') ? $drawing->getOpacity() : 100000;
                //$this->info("Opacity value: {$opacity}/100000");

                if ($opacity >= 100000) {
                    //$this->info("Opacity is 100% (fully opaque). No transparency adjustment needed.");
                    continue;
                }

                $imagePath = $drawing->getPath();
                //$this->info("Image path: {$imagePath}");

                // Images embedded in xlsx are accessed via zip:// stream — file_exists() does not
                // work on zip:// URIs, so read via file_get_contents() which supports all streams.
                $imageData = @file_get_contents($imagePath);
                if ($imageData === false || $imageData === '') {
                    $this->warn("Could not read image data from: {$imagePath}");
                    continue;
                }

                // imagecreatefromstring() auto-detects JPEG / PNG / GIF / WebP
                $src = @imagecreatefromstring($imageData);
                if (!$src) {
                    $this->warn("Could not create image resource from: {$imagePath}");
                    continue;
                }

                $w = imagesx($src);
                $h = imagesy($src);
                //$this->info("Image dimensions: {$w}x{$h}");

                // GD alpha: 0 = fully opaque, 127 = fully transparent
                // Convert opacity (0–100000) → GD alpha (127–0)
                $gdAlpha = 127 - (int)(($opacity / 100000) * 127);
                //$this->info("GD alpha: {$gdAlpha}/127 (0=opaque, 127=transparent)");

                $dst = imagecreatetruecolor($w, $h);
                imagesavealpha($dst, true);
                imagealphablending($dst, false);

                // Apply opacity pixel by pixel so PNG alpha channels are also respected.
                // imagecopymerge() only blends RGB values and ignores true alpha, so we
                // must iterate to set the correct per-pixel alpha.
                for ($x = 0; $x < $w; $x++) {
                    for ($y = 0; $y < $h; $y++) {
                        $rgb  = imagecolorat($src, $x, $y);
                        $r    = ($rgb >> 16) & 0xFF;
                        $g    = ($rgb >> 8)  & 0xFF;
                        $b    =  $rgb        & 0xFF;
                        $srcA = ($rgb >> 24) & 0x7F; // existing GD alpha (0=opaque, 127=transparent)
                        $newA = min(127, $srcA + $gdAlpha);
                        $color = imagecolorallocatealpha($dst, $r, $g, $b, $newA);
                        imagesetpixel($dst, $x, $y, $color);
                    }
                }

                imagedestroy($src);

                $tempPath = tempnam(sys_get_temp_dir(), 'rc_img_') . '.png';
                //$this->info("Saving processed image to: {$tempPath}");
                imagepng($dst, $tempPath);
                imagedestroy($dst);

                $drawing->setPath($tempPath);

                // Reset the drawing's Excel-level opacity to fully opaque — transparency is now
                // baked into the PNG pixel data. Without this Excel would double-apply the effect.
                if (method_exists($drawing, 'setOpacity')) {
                    $drawing->setOpacity(100000);
                }

                $modified = true;

                //$this->info("Baked opacity={$opacity}/100000 into image for drawing #{$drawingIndex} in sheet '{$sheetTitle}'");
            }
        }

        if ($modified) {
            try {
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($filePath);
                //$this->info("Saved modified spreadsheet to: {$filePath}");
            } catch (\Exception $e) {
                $this->error("Failed to save spreadsheet: " . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            //$this->info("No transparency adjustments needed.");
        }

        return self::SUCCESS;
    }
}
