<?php
//POCOR-9812 Starts
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

/**
 * Reports > Custom > Add
 * Lets users with the "Custom Report Setup" permission create a custom report definition
 * (a row in the `reports` table). The report is then generated from Reports > Custom > Process
 * (CustomReportsTable), which lists `reports.name` in its Feature dropdown.
 */
class CustomReportSetupsTable extends ControllerActionTable
{
    // same values as CustomReportsTable (reports.format)
    const CSV = 1;
    const XLSX = 2;

    // statements that change data or schema, write/read server files, or stall the database -
    // a custom report may only read data. Checked after quoted text and comments are removed.
    const FORBIDDEN_SQL = '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|GRANT|REVOKE|RENAME|CALL|HANDLER|LOCK|UNLOCK|LOAD|INTO|SET|PREPARE|EXECUTE|DEALLOCATE|LOAD_FILE|SLEEP|BENCHMARK|GET_LOCK)\b/i';

    public function initialize(array $config): void
    {
        $this->setTable('reports');
        parent::initialize($config);

        // only the Add page is offered; the list of reports is Reports > Custom
        $this->toggle('index', false);
        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->toggle('search', false);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('name', 'create')
            ->notEmptyString('name', __('This field cannot be left empty'))
            ->maxLength('name', 150)
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('This name already exists')
            ])
            ->requirePresence('query', 'create')
            ->notEmptyString('query', __('This field cannot be left empty'))
            ->add('query', 'ruleValidQuery', [
                'rule' => [$this, 'validateQuery'],
                'message' => __('Query must be valid JSON, e.g. {"Data":{"sql":"SELECT ..."}}, and may only contain SELECT statements')
            ])
            // filter and conditions are run through the same query builder as Query on the Process
            // page, so the same read-only rules apply to them
            ->allowEmptyString('filter')
            ->add('filter', 'ruleValidJson', [
                'rule' => [$this, 'validateJsonObject'],
                'message' => __('Filter must be valid JSON and may only contain SELECT statements')
            ])
            ->allowEmptyString('conditions')
            ->add('conditions', 'ruleValidJson', [
                'rule' => [$this, 'validateJsonObject'],
                'message' => __('Conditions must be valid JSON and may only contain SELECT statements')
            ])
            ->requirePresence('format', 'create')
            ->inList('format', [self::CSV, self::XLSX]);
    }

    /**
     * Query must be a JSON object of sheets. A sheet is either a model definition
     * ({"model": ...}) or raw SQL ({"sql": "SELECT ..."}). Raw SQL may only read data, and the
     * text in a model definition (select/where/join/having expressions) is checked the same way
     * because the query builder puts it into the SQL as written.
     */
    public function validateQuery($value, array $context): bool
    {
        $json = json_decode((string)$value, true);
        if (!is_array($json) || empty($json)) {
            return false;
        }
        foreach ($json as $sheet) {
            if (!is_array($sheet) || (!isset($sheet['model']) && !isset($sheet['sql']))) {
                return false;
            }
        }
        return $this->isReadOnlyDefinition($json);
    }

    /**
     * Filter and Conditions: a JSON object whose SQL / expressions (if any) only read data.
     */
    public function validateJsonObject($value, array $context): bool
    {
        $json = json_decode((string)$value, true);
        return is_array($json) && $this->isReadOnlyDefinition($json);
    }

    // keys whose values are data, not SQL: labels, settings, names looked up by the code (filter
    // options, field type, finder name, model name, ...) - their values are not keyword-checked
    const DATA_KEYS = ['options', 'fieldType', 'validate', 'parameters', 'model', 'type', 'name', 'title', 'keyField', 'valueField'];

    /**
     * Walks a decoded definition:
     * - every "sql" value must be a single read-only statement;
     * - every other text value that is used as SQL (select/where/having expressions, join and
     *   finder conditions) must not contain a statement or a forbidden keyword;
     * - every key below the top level (column aliases, field names, condition keys) is put into
     *   the SQL too, so it may not contain quotes, comments or statement separators.
     * The query builder still runs everything read-only (CustomReportBehavior::runReadOnly);
     * this check gives the user a clear message on Save.
     */
    private function isReadOnlyDefinition(array $definition, $parentKey = null, int $depth = 0): bool
    {
        foreach ($definition as $key => $value) {
            if (is_string($key) && $depth > 0 && !$this->isSafeKey($key, $parentKey)) {
                return false;
            }
            if (is_array($value)) {
                if (!$this->isReadOnlyDefinition($value, $key, $depth + 1)) {
                    return false;
                }
            } elseif (is_string($value)) {
                if ($key === 'sql') {
                    $ok = $this->isReadOnlySql($value);
                } elseif (in_array($key, self::DATA_KEYS, true) || in_array($parentKey, ['options', 'parameters'], true)) {
                    $ok = true;
                } else {
                    $ok = $this->isSafeExpression($value);
                }
                if (!$ok) {
                    return false;
                }
            }
        }
        return true;
    }

    private function isSafeKey(string $key, $parentKey): bool
    {
        // quotes, backticks, comments and ; could close an identifier/alias and start new SQL
        if (preg_match('/[`\'";\\\\#]|--|\/\*/', $key)) {
            return false;
        }
        // column aliases are wrapped in backticks, so any word is fine there
        if ($parentKey === 'select') {
            return true;
        }
        return !preg_match(self::FORBIDDEN_SQL, $key);
    }

    private function isReadOnlySql($sql): bool
    {
        if (!is_string($sql)) {
            return false;
        }
        $code = $this->codeOnly($sql);
        if ($code === null) {
            return false;
        }
        // ignore a single trailing semicolon; any other one means more than one statement
        $code = rtrim(trim($code), ';');
        if ($code === '' || strpos($code, ';') !== false) {
            return false;
        }
        if (!preg_match('/^\(*\s*(SELECT|WITH)\b/i', $code)) {
            return false;
        }
        return !preg_match(self::FORBIDDEN_SQL, $code);
    }

    private function isSafeExpression(string $expression): bool
    {
        $code = $this->codeOnly($expression);
        return $code !== null && strpos($code, ';') === false && !preg_match(self::FORBIDDEN_SQL, $code);
    }

    /**
     * Returns the SQL with string literals ('...', "..."), `identifiers` and comments blanked out,
     * read in ONE pass from left to right the way MySQL reads it, so keywords or semicolons inside
     * them (e.g. name = 'Pending Update', LIKE '%a;b%', AS `call`) are not mistaken for
     * statements - and a quote inside a comment or identifier cannot hide real SQL.
     * Returns null for SQL that cannot be read safely: an unterminated quote or comment, or a
     * MySQL executable comment / optimizer hint (/*! ... * /, /*+ ... * /), whose content MySQL runs.
     */
    private function codeOnly(string $sql): ?string
    {
        $out = '';
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $closed = false;
                for ($i++; $i < $length; $i++) {
                    if ($quote !== '`' && $sql[$i] === '\\') {
                        $i++; // backslash escape inside '...' and "..."
                        continue;
                    }
                    if ($sql[$i] === $quote) {
                        if ($i + 1 < $length && $sql[$i + 1] === $quote) {
                            $i++; // doubled quote
                            continue;
                        }
                        $closed = true;
                        break;
                    }
                }
                if (!$closed) {
                    return null;
                }
                $out .= $quote === '`' ? ' `x` ' : " '' ";
                continue;
            }

            if ($char === '/' && $next === '*') {
                $marker = $i + 2 < $length ? $sql[$i + 2] : '';
                if ($marker === '!' || $marker === '+') {
                    return null;
                }
                $end = strpos($sql, '*/', $i + 2);
                if ($end === false) {
                    return null;
                }
                $i = $end + 1;
                $out .= ' ';
                continue;
            }

            // "-- " starts a comment in MySQL only when followed by whitespace/control ("--x" is minus minus x)
            $dashComment = $char === '-' && $next === '-'
                && ($i + 2 >= $length || ctype_space($sql[$i + 2]) || ctype_cntrl($sql[$i + 2]));
            if ($char === '#' || $dashComment) {
                $end = strpos($sql, "\n", $i);
                if ($end === false) {
                    break;
                }
                $i = $end;
                $out .= ' ';
                continue;
            }

            $out .= $char;
        }
        return $out;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $customReportsUrl = ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'CustomReports', 'index'];
        $this->controller->Navigation->substituteCrumb(__('Custom Report Setups'), __('Custom'), $customReportsUrl);
        $this->controller->Navigation->addCrumb(__('Add'));
    }

    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $customReportsUrl = ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'CustomReports', 'index'];
        // after saving, return to Reports > Custom where the new report can be processed
        $extra['redirect'] = $customReportsUrl;
        $extra['toolbarButtons']['back']['url'] = $customReportsUrl;

        $this->field('name', ['type' => 'string']);
        $this->field('query', ['type' => 'text']);
        $this->field('filter', ['type' => 'text']);
        $this->field('conditions', ['type' => 'text']);
        // Excel template fields are not part of this page
        $this->field('excel_template_name', ['visible' => false]);
        $this->field('excel_template', ['visible' => false]);
        $this->field('format', [
            'type' => 'select',
            'select' => false,
            'options' => [self::CSV => __('CSV'), self::XLSX => __('Excel')],
            'default' => self::CSV //POCOR-9812: CSV pre-selected, Excel can still be chosen

        ]);

        $this->setFieldOrder(['name', 'query', 'filter', 'conditions', 'format']);
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {
        foreach ($buttons as $key => $button) {
            if (isset($button['attr']['class']) && strpos($button['attr']['class'], 'btn-cancel') !== false) {
                $buttons[$key]['url'] = ['plugin' => 'Report', 'controller' => 'Reports', 'action' => 'CustomReports', 'index'];
            }
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'query':
                return __('Query');
            case 'filter':
                return __('Filter');
            case 'conditions':
                return __('Conditions');
            case 'format':
                return __('Format');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
//POCOR-9812 Ends
