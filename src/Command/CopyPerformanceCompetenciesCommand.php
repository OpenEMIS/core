<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use App\Command\CopyCommandBase;

class CopyPerformanceCompetenciesCommand extends CopyCommandBase
{
    public static function defaultName(): string
    {
        return 'copy:performance-competencies';
    }

    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $this->addStandardOptions(
            $parser->setDescription('Copy competency templates, items, criterias, and periods from one academic period to another.')
        );
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->initializeFromInput($args, $io);
        $conn = $this->getConnection();
        $conn->begin();

        try {
            $this->logMsg("Starting competency copy: {$this->fromId} ➜ {$this->toId} " . ($this->dryRun ? '[dry-run]' : ''));

            $map = [];
            $templates = $this->getDynamicTableInstance('competency_templates');
            $items = $this->getDynamicTableInstance('competency_items');
            $criterias = $this->getDynamicTableInstance('competency_criterias');
            $periods = $this->getDynamicTableInstance('competency_periods');
            $itemPeriods = $this->getDynamicTableInstance('competency_items_periods');

            $map['templates'] = $this->copyTemplates($templates);
            $map['items']     = $this->copyItems($items, $map['templates']);
            $map['criterias'] = $this->copyCriterias($criterias);
            $map['periods']   = $this->copyPeriods($periods, $map['templates']);
            $this->copyItemPeriods($itemPeriods, $map);

            $this->fixTemplateGrades($this->fromId, $this->toId, $templates);

            if ($this->dryRun) {
                $conn->rollback();
                $this->logMsg("<info>Dry-run complete: rolling back.</info>");
            } else {
                $conn->commit();
                $this->logMsg("Competency data copied successfully.");
            }

            $this->completeProcess();
            return static::CODE_SUCCESS;
        } catch (\Throwable $e) {
            $conn->rollback();
            $this->failProcess($e);
            $io->error("Error: " . $e->getMessage());
            return static::CODE_ERROR;
        }
    }

    private function copyTemplates(Table $table): array
    {
        $this->logMsg("-> Copying templates …");
        $map = [];

        foreach ($table->find()->where(['academic_period_id' => $this->fromId]) as $tpl) {
            /** @var Entity $tpl */
            $clone = $table->newEntity($tpl->toArray());
            unset($clone->id);
            $clone->academic_period_id = $this->toId;
            $clone->created_user_id = $this->userId;
            $clone->created = date('Y-m-d H:i:s');

            $this->saveOrThrow($table, $clone, 'Template');
            $map[$tpl->id] = $clone->id ?? null;
        }

        return $map;
    }

    private function copyItems(Table $table, array $templateMap): array
    {
        $this->logMsg("-> Copying items …");
        $map = [];

        foreach ($table->find()->where(['academic_period_id' => $this->fromId]) as $item) {
            $clone = $table->newEntity($item->toArray());
            unset($clone->id);
            $clone->academic_period_id = $this->toId;
            $clone->competency_template_id = $templateMap[$item->competency_template_id] ?? null;
            $clone->created_user_id = $this->userId;
            $clone->created = date('Y-m-d H:i:s');

            $this->saveOrThrow($table, $clone, 'Item');
            $map[$item->id] = $clone->id ?? null;
        }

        return $map;
    }

    private function copyCriterias(Table $table): array
    {
        $this->logMsg("-> Copying criterias …");
        $map = [];

        foreach ($table->find()->where(['academic_period_id' => $this->fromId]) as $c) {
            $clone = $table->newEntity($c->toArray());
            unset($clone->id);
            $clone->academic_period_id = $this->toId;
            $clone->created_user_id = $this->userId;
            $clone->created = date('Y-m-d H:i:s');

            $this->saveOrThrow($table, $clone, 'Criteria');
            $map[$c->id] = $clone->id ?? null;
        }

        return $map;
    }

    private function copyPeriods(Table $table, array $templateMap): array
    {
        $this->logMsg("-> Copying periods …");
        $map = [];
        $inserted = 0;
        $existing = 0;

        foreach ($table->find()->where(['academic_period_id' => $this->fromId]) as $p) {
            $exists = $table->find()->where(['code' => $p->code, 'academic_period_id' => $this->toId])->first();

            if ($exists) {
                $map[$p->id] = $exists->id;
                $existing++;
                $this->logMsg("  ↺ Period already exists -> {$exists->id} ({$p->code})");
                continue;
            }

            $newId = $this->nextIncrement($table);

            $clone = $table->newEntity([
                'id' => $newId,
                'code' => $p->code,
                'name' => $p->name,
                'start_date' => $p->start_date,
                'end_date' => $p->end_date,
                'date_enabled' => $p->date_enabled,
                'date_disabled' => $p->date_disabled,
                'academic_period_id' => $this->toId,
                'competency_template_id' => $templateMap[$p->competency_template_id] ?? null,
                'created_user_id' => $this->userId,
                'created' => date('Y-m-d H:i:s'),
                'modified_user_id' => null,
                'modified' => null
            ]);

            if (!$clone->competency_template_id) {
                $this->logMsg("  ~ Skipped period {$p->id}: missing template map");
                continue;
            }

            $this->saveOrThrow($table, $clone, 'Period');
            $map[$p->id] = $clone->id;
            $inserted++;
        }

        $this->logMsg("  Periods: inserted={$inserted}, existing={$existing}");
        return $map;
    }

    private function copyItemPeriods(Table $table, array $map): void
    {
        $this->logMsg("-> Copying item-period links …");
        $inserted = $existing = $skipped = $failed = 0;

        foreach ($table->find()->where(['academic_period_id' => $this->fromId]) as $ip) {
            $oldItemId = (int)$ip->competency_item_id;
            $oldPeriodId = (int)$ip->competency_period_id;
            $oldTplId = (int)$ip->competency_template_id;

            $newItemId = $map['items'][$oldItemId] ?? null;
            $newPeriodId = $map['periods'][$oldPeriodId] ?? null;
            $newTplId = $map['templates'][$oldTplId] ?? null;

            if (!$newItemId || !$newPeriodId || !$newTplId) {
                $skipped++;
                $this->logMsg("  ~ Skipping item-period: missing mapping for item={$oldItemId}, period={$oldPeriodId}, template={$oldTplId}");
                continue;
            }

            $exists = $this->conn->execute(
                "SELECT 1 FROM competency_items_periods
                 WHERE competency_item_id = :item
                   AND competency_period_id = :period
                   AND academic_period_id = :ap
                   AND competency_template_id = :tpl
                 LIMIT 1",
                [
                    'item' => $newItemId,
                    'period' => $newPeriodId,
                    'ap' => $this->toId,
                    'tpl' => $newTplId,
                ]
            )->fetch('assoc');

            if ($exists) {
                $existing++;
                continue;
            }

            if ($this->dryRun) {
                $inserted++;
                $this->logMsg(" ? [dry-run] Would insert item-period link: item={$newItemId}, period={$newPeriodId}, tpl={$newTplId}");
                continue;
            }

            try {
                $this->conn->execute(
                    "INSERT INTO competency_items_periods
                    (id, competency_item_id, competency_period_id, academic_period_id, competency_template_id,
                     created_user_id, created)
                    VALUES (:id, :item, :period, :ap, :tpl, :uid, :ts)",
                    [
                        'id' => $this->uuid(),
                        'item' => $newItemId,
                        'period' => $newPeriodId,
                        'ap' => $this->toId,
                        'tpl' => $newTplId,
                        'uid' => $this->userId,
                        'ts' => date('Y-m-d H:i:s')
                    ]
                );
                $inserted++;
            } catch (\Throwable $e) {
                $failed++;
                $this->io->err("  x Failed to insert item-period link: {$e->getMessage()}");
            }
        }

        $this->logMsg("  ItemsPeriods: inserted={$inserted}, existing={$existing}, skipped={$skipped}, failed={$failed}");
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function nextIncrement(Table $table): int
    {
        $row = $table->find()->select(['max_id' => 'MAX(id)'])->first();
        return ((int)$row->max_id) + 1;
    }

    private function fixTemplateGrades(int $fromId, int $toId, Table $templates): void
    {
        $sql = <<<SQL
SELECT subq1.grade_id AS wrong_grade, subq2.grade_id AS correct_grade
FROM
(
    SELECT g.id AS grade_id, g.name AS grade_name
    FROM education_grades g
    INNER JOIN education_programmes p ON g.education_programme_id = p.id
    INNER JOIN education_cycles c ON p.education_cycle_id = c.id
    INNER JOIN education_levels l ON c.education_level_id = l.id
    INNER JOIN education_systems s ON l.education_system_id = s.id
    WHERE s.academic_period_id = :fromId
) subq1
JOIN
(
    SELECT g.id AS grade_id, g.name AS grade_name
    FROM education_grades g
    INNER JOIN education_programmes p ON g.education_programme_id = p.id
    INNER JOIN education_cycles c ON p.education_cycle_id = c.id
    INNER JOIN education_levels l ON c.education_level_id = l.id
    INNER JOIN education_systems s ON l.education_system_id = s.id
    WHERE s.academic_period_id = :toId
) subq2
ON subq1.grade_name = subq2.grade_name
SQL;

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('fromId', $fromId, 'integer');
        $stmt->bindValue('toId', $toId, 'integer');
        $stmt->execute();

        foreach ($stmt->fetchAll('assoc') as $row) {
            $templates->updateAll(
                ['education_grade_id' => (int)$row['correct_grade']],
                ['education_grade_id' => (int)$row['wrong_grade'], 'academic_period_id' => $toId]
            );
        }

        $this->logMsg("✔ Fixed education_grade_id mismatches in templates");
    }
}
