<?php
namespace Staff\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\I18n\FrozenDate;

/**
 * Staff Salary Behavior
 *
 * Handles salary calculation for staff positions
 * based on base salary, FTE, and yearly increments.
 *
 * @author shikha.sahu@dataforall.org
 */
class StaffSalaryBehavior extends Behavior
{
    // POCOR-8211: request-scoped caches to avoid re-querying the same
    // academic period / grade / increment rows for every staff position row.
    private $_academicPeriodCache = [];
    private $_gradeCache = [];
    private $_incrementCache = [];

    /**
     * Calculate staff position salary.
     *
     * Formula:
     * (Total Increment % / 100) * Base Salary * (FTE / 100)
     *
     *  @param object $entity Staff position entity with properties:
     * @return float Calculated salary rounded to 2 decimal places
     */
    public function calculateStaffPositionSalary($entity)
    {
        // Safety checks
        if (empty($entity->start_date) || empty($entity->staff_position_grade)) {
            return 0;
        }

        // 1. Base Salary
        $baseSalary = $this->getGradeSalary($entity->staff_position_grade);

        // 2. FTE
        $fte = 100;
        if ($entity->FTE < 1) {
            $fte = ($entity->FTE * 100);
        }

        // 3. Year Range
        $startYear = $entity->start_date->year;
        $currentYear = FrozenDate::today()->year;

        // 4. SUM of increments
        $totalIncrement = 0;

        for ($year = $startYear + 1; $year <= $currentYear; $year++) {

            $academicPeriodId = $this->getAcademicPeriodId($year);

            if (!$academicPeriodId) {
                continue; // treat as 0%
            }

            $totalIncrement += $this->getIncrement($entity->staff_position_grade, $academicPeriodId);
        }
        // 5. Apply formula
        $salary = ($totalIncrement / 100) * $baseSalary * $fte/100;
        return round($salary, 2);
    }

    /**
     * Get the base salary for a Staff Position Grade, cached per request
     * so the same grade isn't re-fetched for every staff position row.
     *
     * @param int $gradeId Staff Position Grade ID
     * @return float Base salary
     */
    private function getGradeSalary($gradeId)
    {
        if (!array_key_exists($gradeId, $this->_gradeCache)) {
            $gradesTable = TableRegistry::getTableLocator()->get('Institution.StaffPositionGrades');
            $grade = $gradesTable->get($gradeId);
            $this->_gradeCache[$gradeId] = $grade->salary ?? 0;
        }

        return $this->_gradeCache[$gradeId];
    }

    /**
     * Get the increment percentage for a grade/academic period, cached per
     * request so the same combination isn't re-queried for every row.
     *
     * @param int $gradeId Staff Position Grade ID
     * @param int $academicPeriodId Academic Period ID
     * @return float Increment percentage
     */
    private function getIncrement($gradeId, $academicPeriodId)
    {
        $cacheKey = $gradeId . ':' . $academicPeriodId;

        if (!array_key_exists($cacheKey, $this->_incrementCache)) {
            $incrementsTable = TableRegistry::getTableLocator()->get('System.StaffSalaries');
            $incrementRow = $incrementsTable->find()
                ->where([
                    'staff_position_grade_id' => $gradeId,
                    'academic_period_id' => $academicPeriodId
                ])
                ->select(['increment'])
                ->first();

            $this->_incrementCache[$cacheKey] = $incrementRow->increment ?? 0;
        }

        return $this->_incrementCache[$cacheKey];
    }

    /**
     * Get academic period ID by year, cached per request so the same year
     * isn't re-queried for every staff position row.
     *
     * Finds the academic period where the start_date
     * belongs to the given year.
     *
     * @param int $year Academic year
     * @return int|null Academic period ID or null if not found
     */
    private function getAcademicPeriodId($year)
    {
        if (!array_key_exists($year, $this->_academicPeriodCache)) {
            $row = $this->_table->getConnection()->execute(
                "SELECT id FROM academic_periods WHERE YEAR(start_date) = :year LIMIT 1",
                ['year' => $year]
            )->fetch('assoc');

            $this->_academicPeriodCache[$year] = $row['id'] ?? null;
        }

        return $this->_academicPeriodCache[$year];
    }
}