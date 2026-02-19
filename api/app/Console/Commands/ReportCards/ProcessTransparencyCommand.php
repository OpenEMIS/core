<?php

namespace App\Console\Commands\ReportCards;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ProcessTransparencyCommand extends Command
{
    protected $signature = 'reportcards:fix-transparency {filePath : Absolute path to xlsx file}';

    protected $description = 'Apply GD transparency to embedded drawings in an xlsx report card template';

    public function handle(): int
    {
        $filePath = $this->argument('filePath');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            $this->error("Failed to load spreadsheet: " . $e->getMessage());
            return self::FAILURE;
        }

        $modified = false;

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            foreach ($sheet->getDrawingCollection() as $drawing) {
                if (!($drawing instanceof Drawing)) {
                    continue;
                }

                $alpha = method_exists($drawing, 'getAlpha') ? $drawing->getAlpha() : 0;

                if ($alpha <= 0) {
                    continue;
                }

                $imagePath = $drawing->getPath();

                if (!file_exists($imagePath)) {
                    $this->warn("Image path not found: {$imagePath}");
                    continue;
                }

                $mimeType = mime_content_type($imagePath);

                switch ($mimeType) {
                    case 'image/png':
                        $src = imagecreatefrompng($imagePath);
                        break;
                    case 'image/jpeg':
                    case 'image/jpg':
                        $src = imagecreatefromjpeg($imagePath);
                        break;
                    case 'image/gif':
                        $src = imagecreatefromgif($imagePath);
                        break;
                    default:
                        $this->warn("Unsupported image type '{$mimeType}' for: {$imagePath}");
                        continue 2;
                }

                if (!$src) {
                    $this->warn("Could not create image resource for: {$imagePath}");
                    continue;
                }

                $w   = imagesx($src);
                $h   = imagesy($src);
                $dst = imagecreatetruecolor($w, $h);

                imagesavealpha($dst, true);
                imagealphablending($dst, false);

                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $w, $h, $transparent);

                imagealphablending($dst, true);

                // alpha 0–100 in PhpSpreadsheet 2.x; 100 = fully transparent
                // imagecopymerge pct: 0 = fully transparent, 100 = fully opaque
                $pct = 100 - $alpha;
                imagecopymerge($dst, $src, 0, 0, 0, 0, $w, $h, $pct);

                $tempPath = tempnam(sys_get_temp_dir(), 'rc_img_') . '.png';
                imagesavealpha($dst, true);
                imagepng($dst, $tempPath);

                imagedestroy($src);
                imagedestroy($dst);

                $drawing->setPath($tempPath);
                $modified = true;

                $this->info("Processed transparency (alpha={$alpha}%) for drawing in sheet '{$sheet->getTitle()}'");
            }
        }

        if ($modified) {
            try {
                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save($filePath);
                $this->info("Saved modified spreadsheet to: {$filePath}");
            } catch (\Exception $e) {
                $this->error("Failed to save spreadsheet: " . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            $this->info("No transparency adjustments needed.");
        }

        return self::SUCCESS;
    }
}
