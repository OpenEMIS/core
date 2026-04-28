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
       
        // Tables
        $gradesTable = TableRegistry::getTableLocator()->get('Institution.StaffPositionGrades');
        $incrementsTable = TableRegistry::getTableLocator()->get('System.StaffSalaries');

        // 1. Base Salary

        $grade = $gradesTable->get($entity->staff_position_grade);
        $baseSalary = $grade->salary ?? 0;

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

            $incrementRow = $incrementsTable->find()
                ->where([
                    'staff_position_grade_id' => $entity->staff_position_grade,
                    'academic_period_id' => $academicPeriodId
                ])
                ->select(['increment'])
                ->first();

            $increment = $incrementRow->increment ?? 0;

            $totalIncrement += $increment;
        }
        // 5. Apply formula
        $salary = ($totalIncrement / 100) * $baseSalary * $fte/100;
        return round($salary, 2);
    }

    /**
     * Get academic period ID by year.
     *
     * Finds the academic period where the start_date
     * belongs to the given year.
     *
     * @param int $year Academic year
     * @return int|null Academic period ID or null if not found
     */
    private function getAcademicPeriodId($year)
    {
        $row = $this->_table->getConnection()->execute(
            "SELECT id FROM academic_periods WHERE YEAR(start_date) = :year LIMIT 1",
            ['year' => $year]
        )->fetch('assoc');

        return $row['id'] ?? null;
    }

    
}