<?php

namespace Database\Factories;

use App\Models\ReportCards;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReportCardsFactory extends Factory
{
    protected $model = ReportCards::class;

    public function definition(): array
    {


        return [
            'id' => $this->generateUniqueId(),
            'code' => $this->faker->lexify(str_repeat("?", 50)),
            'name' => $this->faker->lexify(str_repeat("?", 150)),
            'description' => $this->faker->text(50),
            'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
            'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
            'generate_start_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
            'generate_end_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
            'principal_comments_required' => $this->faker->numberBetween(1, 1000),
            'homeroom_teacher_comments_required' => $this->faker->numberBetween(1, 1000),
            'teacher_comments_required' => $this->faker->numberBetween(1, 1000),
            'excel_template_name' => $this->faker->lexify(str_repeat("?", 250)),
            'excel_template' => $this->faker->word(),
            'pdf_page_number' => $this->faker->numberBetween(1, 1000),
            'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
            'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
            'modified_user_id' => $this->faker->numberBetween(1, 2),
            'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
            'created_user_id' => $this->faker->numberBetween(1, 2),
            'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
        ];
    }

    protected function generateUniqueId(): int
    {
        do {
            $id = $this->model::max('id') + 1; // Generate a new ID
        } while (\DB::table('report_card_subjects')->where('report_card_id', $id)->exists()); // Check if the ID is used

        return $id;
    }
}
