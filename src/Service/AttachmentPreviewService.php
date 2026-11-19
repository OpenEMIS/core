<?php

namespace App\Service;

use Cake\Core\Configure;
use RuntimeException;
use ZipArchive;

/**
 * POCOR-9904: builds an in-browser preview of an uploaded attachment.
 *
 *  - pdf / images          served as-is (the browser renders them)
 *  - txt / csv             served as plain text
 *  - office documents      converted to PDF with LibreOffice (headless), cached by content hash
 *  - zip                   an HTML page listing the archive contents
 *
 * Nothing user-uploaded is ever served with an HTML/SVG content type, so an uploaded file can never run
 * script in our origin. The only HTML served is the zip listing, which is generated here and fully escaped.
 */
class AttachmentPreviewService
{
    public const MODE_INLINE = 'inline';
    public const MODE_TEXT = 'text';
    public const MODE_CONVERT = 'convert';
    public const MODE_ZIP = 'zip';

    private const INLINE_TYPES = [
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
    ];

    private const TEXT_TYPES = ['txt', 'csv'];

    private const CONVERT_TYPES = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'rtf', 'odt', 'ods', 'odp'];

    private const ZIP_TYPES = ['zip'];

    // Converted PDFs older than this are removed the next time a conversion runs.
    private const CACHE_LIFETIME = 86400;

    private static $converterPath = false; // false = not resolved yet

    /**
     * How a file would be previewed, or null if its type cannot be previewed.
     */
    public static function getMode(?string $fileName): ?string
    {
        $extension = self::getExtension($fileName);
        if ($extension === '') {
            return null;
        }
        if (isset(self::INLINE_TYPES[$extension])) {
            return self::MODE_INLINE;
        }
        if (in_array($extension, self::TEXT_TYPES, true)) {
            return self::MODE_TEXT;
        }
        if (in_array($extension, self::CONVERT_TYPES, true)) {
            return self::MODE_CONVERT;
        }
        if (in_array($extension, self::ZIP_TYPES, true)) {
            return self::MODE_ZIP;
        }
        return null;
    }

    /**
     * Whether this server can preview the file (office files need LibreOffice, zip needs ext-zip).
     */
    public static function canPreview(?string $fileName): bool
    {
        switch (self::getMode($fileName)) {
            case self::MODE_INLINE:
            case self::MODE_TEXT:
                return true;
            case self::MODE_CONVERT:
                return self::getConverterPath() !== null;
            case self::MODE_ZIP:
                return class_exists(ZipArchive::class);
            default:
                return false;
        }
    }

    /**
     * @return array{type: string, body: string, fileName: string, html: bool}
     * @throws RuntimeException when the file cannot be previewed
     */
    public static function build(string $fileName, string $content): array
    {
        $extension = self::getExtension($fileName);

        switch (self::getMode($fileName)) {
            case self::MODE_INLINE:
                return [
                    'type' => self::INLINE_TYPES[$extension],
                    'body' => $content,
                    'fileName' => $fileName,
                    'html' => false,
                ];

            case self::MODE_TEXT:
                if (!mb_check_encoding($content, 'UTF-8')) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
                }
                return [
                    'type' => 'text/plain; charset=UTF-8',
                    'body' => $content,
                    'fileName' => $fileName,
                    'html' => false,
                ];

            case self::MODE_CONVERT:
                return [
                    'type' => 'application/pdf',
                    'body' => self::convertToPdf($extension, $content),
                    'fileName' => pathinfo($fileName, PATHINFO_FILENAME) . '.pdf',
                    'html' => false,
                ];

            case self::MODE_ZIP:
                return [
                    'type' => 'text/html; charset=UTF-8',
                    'body' => self::buildZipListing($fileName, $content),
                    'fileName' => $fileName,
                    'html' => true,
                ];

            default:
                throw new RuntimeException('Preview is not supported for this file type.');
        }
    }

    private static function getExtension(?string $fileName): string
    {
        if (empty($fileName)) {
            return '';
        }
        return strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
    }

    /******************************************************************************************************************
     ** Office -> PDF (LibreOffice)
     ******************************************************************************************************************/

    /**
     * LibreOffice executable: Attachment.libreOfficePath (env LIBREOFFICE_PATH) if set, otherwise the
     * usual install locations. Null when not installed or proc_open is disabled.
     */
    public static function getConverterPath(): ?string
    {
        if (self::$converterPath !== false) {
            return self::$converterPath;
        }

        self::$converterPath = null;
        if (!function_exists('proc_open')) {
            return null;
        }

        $configured = Configure::read('Attachment.libreOfficePath');
        $candidates = !empty($configured) ? [$configured] : [
            // Windows: soffice.com is the console launcher and waits for the conversion to finish
            'C:\\Program Files\\LibreOffice\\program\\soffice.com',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.com',
            '/usr/bin/soffice',
            '/usr/bin/libreoffice',
            '/usr/local/bin/soffice',
            '/usr/lib/libreoffice/program/soffice',
            '/opt/libreoffice/program/soffice',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice',
        ];

        if (empty($configured)) {
            // Report card PDF printing (CustomExcel PdfReportTrait) runs `libreoffice` from PATH, so look
            // there too: wherever report cards can convert, attachments can be previewed.
            $candidates = array_merge($candidates, self::findOnPath(['soffice', 'libreoffice']));
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                self::$converterPath = $candidate;
                break;
            }
        }

        return self::$converterPath;
    }

    private static function findOnPath(array $names): array
    {
        $found = [];
        $isWindows = DIRECTORY_SEPARATOR === '\\';
        foreach (explode(PATH_SEPARATOR, (string)getenv('PATH')) as $dir) {
            $dir = trim($dir, " \"'");
            if ($dir === '') {
                continue;
            }
            foreach ($names as $name) {
                foreach ($isWindows ? [$name . '.com', $name . '.exe'] : [$name] as $file) {
                    $path = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $file;
                    if (is_file($path) && ($isWindows || is_executable($path))) {
                        $found[] = $path;
                    }
                }
            }
        }
        return $found;
    }

    private static function convertToPdf(string $extension, string $content): string
    {
        $converter = self::getConverterPath();
        if ($converter === null) {
            throw new RuntimeException('LibreOffice is not installed on this server.');
        }

        $cacheDir = TMP . 'attachment_preview' . DS;
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0770, true) && !is_dir($cacheDir)) {
            throw new RuntimeException('Cannot create preview cache directory ' . $cacheDir);
        }

        // Keyed by content, so an edited attachment gets a fresh conversion automatically.
        $cacheFile = $cacheDir . sha1($content) . '.pdf';
        if (is_file($cacheFile)) {
            @touch($cacheFile);
            return (string)file_get_contents($cacheFile);
        }

        self::removeExpiredCache($cacheDir);

        // Each conversion gets its own work dir and LibreOffice profile, so parallel requests never clash.
        $workDir = $cacheDir . 'work_' . bin2hex(random_bytes(8)) . DS;
        mkdir($workDir, 0770, true);

        try {
            // $extension comes from the whitelist above, so the file name is fixed and safe.
            $source = $workDir . 'source.' . $extension;
            file_put_contents($source, $content);

            $command = [
                $converter,
                '--headless',
                '--norestore',
                '--nolockcheck',
                '--nodefault',
                '--nologo',
                '-env:UserInstallation=' . self::toFileUri($workDir . 'profile'),
                '--convert-to', 'pdf',
                '--outdir', rtrim($workDir, DS),
                $source,
            ];

            // Output goes to files rather than pipes: avoids pipe-buffer deadlocks, and works on Windows.
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['file', $workDir . 'stdout.log', 'w'],
                2 => ['file', $workDir . 'stderr.log', 'w'],
            ];

            // Array form runs the binary directly, without a shell, so nothing needs escaping.
            $process = proc_open($command, $descriptors, $pipes, $workDir);
            if (!is_resource($process)) {
                throw new RuntimeException('Could not start LibreOffice.');
            }
            fclose($pipes[0]);

            $timeout = (int)Configure::read('Attachment.previewTimeout', 60);
            $started = time();
            while (true) {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }
                if (time() - $started >= $timeout) {
                    proc_terminate($process, 9);
                    proc_close($process);
                    throw new RuntimeException('LibreOffice conversion timed out after ' . $timeout . 's.');
                }
                usleep(100000);
            }
            proc_close($process);

            $output = $workDir . 'source.pdf';
            if (!is_file($output) || filesize($output) === 0) {
                $stderr = is_file($workDir . 'stderr.log') ? trim((string)file_get_contents($workDir . 'stderr.log')) : '';
                throw new RuntimeException('LibreOffice did not produce a PDF. ' . $stderr);
            }

            $pdf = (string)file_get_contents($output);
            @rename($output, $cacheFile);

            return $pdf;
        } finally {
            self::removeDirectory($workDir);
        }
    }

    private static function toFileUri(string $path): string
    {
        $path = str_replace(['\\', ' '], ['/', '%20'], $path);
        return 'file://' . (substr($path, 0, 1) === '/' ? '' : '/') . $path;
    }

    private static function removeExpiredCache(string $cacheDir): void
    {
        foreach (glob($cacheDir . '*.pdf') ?: [] as $file) {
            if (filemtime($file) < time() - self::CACHE_LIFETIME) {
                @unlink($file);
            }
        }
        // Work dirs left behind by a killed request.
        foreach (glob($cacheDir . 'work_*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (filemtime($dir) < time() - 3600) {
                self::removeDirectory($dir);
            }
        }
    }

    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    /******************************************************************************************************************
     ** zip -> listing page
     ******************************************************************************************************************/

    private static function buildZipListing(string $fileName, string $content): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('The zip extension is not available on this server.');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'oe_zip_');
        file_put_contents($tmpFile, $content);

        try {
            $zip = new ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                throw new RuntimeException('The zip file could not be opened.');
            }

            $rows = '';
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    continue;
                }
                $isFolder = substr($stat['name'], -1) === '/';
                $rows .= '<tr><td>' . h($stat['name']) . '</td>'
                    . '<td class="num">' . ($isFolder ? '' : h(self::formatBytes((int)$stat['size']))) . '</td>'
                    . '<td>' . h(date('Y-m-d H:i', (int)$stat['mtime'])) . '</td></tr>';
            }
            $count = $zip->numFiles;
            $zip->close();
        } finally {
            @unlink($tmpFile);
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="3">' . h(__('This zip file is empty.')) . '</td></tr>';
        }

        return '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . h($fileName) . '</title>'
            . '<style>body{font-family:Arial,Helvetica,sans-serif;margin:24px;color:#333}'
            . 'h1{font-size:18px;word-break:break-all}p{color:#666;font-size:13px}'
            . 'table{border-collapse:collapse;width:100%;font-size:13px}'
            . 'th,td{border-bottom:1px solid #ddd;padding:6px 8px;text-align:left;word-break:break-all}'
            . 'th{background:#f5f5f5}.num{text-align:right;white-space:nowrap}</style>'
            . '</head><body>'
            . '<h1>' . h($fileName) . '</h1>'
            . '<p>' . h(sprintf(__('%s item(s). Download the attachment to open these files.'), $count)) . '</p>'
            . '<table><thead><tr><th>' . h(__('Name')) . '</th><th class="num">' . h(__('Size')) . '</th><th>'
            . h(__('Modified')) . '</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '</body></html>';
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float)$bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return ($i === 0 ? $size : number_format($size, 1)) . ' ' . $units[$i];
    }
}
