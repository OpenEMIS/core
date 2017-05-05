<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Dumper;

use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\DumperInterface;

/**
 * Abstract mechanism for dumping a Data object.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
abstract class AbstractDumper implements DataDumperInterface, DumperInterface
{
    const DUMP_LIGHT_ARRAY = 1;
    const DUMP_STRING_LENGTH = 2;

    public static $defaultOutput = 'php://output';

    protected $line = '';
    protected $lineDumper;
    protected $outputStream;
    protected $decimalPoint; // This is locale dependent
    protected $indentPad = '  ';
    protected $flags;

    private $charset;

    /**
     * @param callable|resource|string|null $output  A line dumper callable, an opened stream or an output path, defaults to static::$defaultOutput
     * @param string                        $charset The default character encoding to use for non-UTF8 strings
     * @param int                           $flags   A bit field of static::DUMP_* constants to fine tune dumps representation
     */
    public function __construct($output = null, $charset = null, $flags = 0)
    {
        $this->flags = (int) $flags;
        $this->setCharset($charset ?: ini_get('php.output_encoding') ?: ini_get('default_charset') ?: 'UTF-8');
        $this->decimalPoint = (string) 0.5;
        $this->decimalPoint = $this->decimalPoint[1];
        $this->setOutput($output ?: static::$defaultOutput);
        if (!$output && is_string(static::$defaultOutput)) {
            static::$defaultOutput = $this->outputStream;
        }
    }

    /**
     * Sets the output destination of the dumps.
     *
     * @param callable|resource|string $output A line dumper callable, an opened stream or an output path
     *
     * @return callable|resource|string The previous output destination
     */
    public function setOutput($output)
    {
        $prev = null !== $this->outputStream ? $this->outputStream : $this->lineDumper;

        if (is_callable($output)) {
            $this->outputStream = null;
            $this->lineDumper = $output;
        } else {
            if (is_string($output)) {
                $output = fopen($output, 'wb');
            }
            $this->outputStream = $output;
            $this->lineDumper = array($this, 'echoLine');
        }

        return $prev;
    }

    /**
     * Sets the default character encoding to use for non-UTF8 strings.
     *
     * @param string $charset The default character encoding to use for non-UTF8 strings
     *
     * @return string The previous charset
     */
    public function setCharset($charset)
    {
        $prev = $this->charset;

        $charset = strtoupper($charset);
        $charset = null === $charset || 'UTF-8' === $charset || 'UTF8' === $charset ? 'CP1252' : $charset;

        $this->charset = $charset;

        return $prev;
    }

    /**
     * Sets the indentation pad string.
     *
     * @param string $pad A string the will be prepended to dumped lines, repeated by nesting level
     *
     * @return string The indent pad
     */
    public function setIndentPad($pad)
    {
        $prev = $this->indentPad;
        $this->indentPad = $pad;

        return $prev;
    }

    /**
     * Dumps a Data object.
     *
     * @param Data                               $data   A Data object
     * @param callable|resource|string|true|null $output A line dumper callable, an opened stream, an output path or true to return the dump
     *
     * @return string|null The dump as string when $output is true
     */
    public function dump(Data $data, $output = null)
    {
        if ($returnDump = true === $output) {
            $output = fopen('php://memory', 'r+b');
        }
        if ($output) {
            $prevOutput = $this->setOutput($output);
        }
        try {
            $data->dump($this);
            $this->dumpLine(-1);

            if ($returnDump) {
                $result = stream_get_contents($output, -1, 0);
                fclose($output);

                return $result;
            }
        } finally {
            if ($output) {
                $this->setOutput($prevOutput);
            }
        }
    }

    /**
     * Dumps the current line.
     *
     * @param int $depth The recursive depth in the dumped structure for the line being dumped
     */
    protected function dumpLine($depth)
    {
        call_user_func($this->lineDumper, $this->line, $depth, $this->indentPad);
        $this->line = '';
    }

    /**
     * Generic line dumper callback.
     *
     * @param string $line      The line to write
     * @param int    $depth     The recursive depth in the dumped structure
     * @param string $indentPad The line indent pad
     */
    protected function echoLine($line, $depth, $indentPad)
    {
        if (-1 !== $depth) {
            fwrite($this->outputStream, str_repeat($indentPad, $depth).$line."\n");
        }
    }

    /**
     * Converts a non-UTF-8 string to UTF-8.
     *
     * @param string $s The non-UTF-8 string to convert
     *
     * @return string The string converted to UTF-8
     */
    protected function utf8Encode($s)
    {
        if (preg_match('//u', $s)) {
            return $s;
        }
        if (false !== $c = @iconv($this->charset, 'UTF-8', $s)) {
            return $c;
        }
        if ('CP1252' !== $this->charset && false !== $c = @iconv('CP1252', 'UTF-8', $s)) {
            return $c;
        }

        return iconv('CP850', 'UTF-8', $s);
    }
}
