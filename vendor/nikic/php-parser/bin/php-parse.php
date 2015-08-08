#!/usr/bin/env php
<?php

require __DIR__ . '/../lib/bootstrap.php';

ini_set('xdebug.max_nesting_level', 3000);

// Disable XDebug var_dump() output truncation
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data', -1);
ini_set('xdebug.var_display_max_depth', -1);

list($operations, $files, $attributes) = parseArgs($argv);

/* Dump nodes by default */
if (empty($operations)) {
    $operations[] = 'dump';
}

if (empty($files)) {
    showHelp("Must specify at least one file.");
}

$lexer = new PhpParser\Lexer\Emulative(array('usedAttributes' => array(
    'startLine', 'endLine', 'startFilePos', 'endFilePos'
)));
$parser = new PhpParser\Parser($lexer);
$dumper = new PhpParser\NodeDumper;
$prettyPrinter = new PhpParser\PrettyPrinter\Standard;
$serializer = new PhpParser\Serializer\XML;

$traverser = new PhpParser\NodeTraverser();
$traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver);

foreach ($files as $file) {
    if (strpos($file, '<?php') === 0) {
        $code = $file;
        echo "====> Code $code\n";
    } else {
        if (!file_exists($file)) {
            die("File $file does not exist.\n");
        }

        $code = file_get_contents($file);
        echo "====> File $file:\n";
    }

    try {
        $stmts = $parser->parse($code);
    } catch (PhpParser\Error $e) {
        if ($attributes['with-column-info'] && $e->hasColumnInfo()) {
            $startLine = $e->getStartLine();
            $endLine = $e->getEndLine();
            $startColumn = $e->getStartColumn($code);
            $endColumn   = $e->getEndColumn($code);
            $message .= $e->getRawMessage() . " from $startLine:$startColumn to $endLine:$endColumn";
        } else {
            $message = $e->getMessage();
        }

        die($message . "\n");
    }

    foreach ($operations as $operation) {
        if ('dump' === $operation) {
            echo "==> Node dump:\n";
            echo $dumper->dump($stmts), "\n";
        } elseif ('pretty-print' === $operation) {
            echo "==> Pretty print:\n";
            echo $prettyPrinter->prettyPrintFile($stmts), "\n";
        } elseif ('serialize-xml' === $operation) {
            echo "==> Serialized XML:\n";
            echo $serializer->serialize($stmts), "\n";
        } elseif ('var-dump' === $operation) {
            echo "==> var_dump():\n";
            var_dump($stmts);
        } elseif ('resolve-names' === $operation) {
            echo "==> Resolved names.\n";
            $stmts = $traverser->traverse($stmts);
        }
    }
}

function showHelp($error) {
    die($error . "\n\n" .
        <<<OUTPUT
Usage: php php-parse.php [operations] file1.php [file2.php ...]
   or: php php-parse.php [operations] "<?php code"
Turn PHP source code into an abstract syntax tree.

Operations is a list of the following options (--dump by default):

    -d, --dump              Dump nodes using NodeDumper
    -p, --pretty-print      Pretty print file using PrettyPrinter\Standard
        --serialize-xml     Serialize nodes using Serializer\XML
        --var-dump          var_dump() nodes (for exact structure)
    -N, --resolve-names     Resolve names using NodeVisitor\NameResolver
    -c, --with-column-info  Show column-numbers for errors (if available)

Example:
    php php-parse.php -d -p -N -d file.php

    Dumps nodes, pretty prints them, then resolves names and dumps them again.


OUTPUT
    );
}

function parseArgs($args) {
    $operations = array();
    $files = array();
    $attributes = array(
        'with-column-info' => false,
    );

    array_shift($args);
    $parseOptions = true;
    foreach ($args as $arg) {
        if (!$parseOptions) {
            $files[] = $arg;
            continue;
        }

        switch ($arg) {
            case '--dump':
            case '-d':
                $operations[] = 'dump';
                break;
            case '--pretty-print':
            case '-p':
                $operations[] = 'pretty-print';
                break;
            case '--serialize-xml':
                $operations[] = 'serialize-xml';
                break;
            case '--var-dump':
                $operations[] = 'var-dump';
                break;
            case '--resolve-names':
            case '-N';
                $operations[] = 'resolve-names';
                break;
            case '--with-column-info':
            case '-c';
                $attributes['with-column-info'] = true;
                break;
            case '--':
                $parseOptions = false;
                break;
            default:
                if ($arg[0] === '-') {
                    showHelp("Invalid operation $arg.");
                } else {
                    $files[] = $arg;
                }
        }
    }

    return array($operations, $files, $attributes);
}
