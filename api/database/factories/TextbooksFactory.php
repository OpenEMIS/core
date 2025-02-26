<?php

namespace Database\Factories;

use App\Models\Textbooks;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TextbooksFactory extends Factory
{
    protected $model = Textbooks::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $id = $this->faker->randomElement(\App\Models\Textbooks::pluck('id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\Textbooks::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = Textbooks::where('id', $id)
                ->where('academic_period_id', $academic_period_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'title' => $this->faker->lexify(str_repeat("?", 100)),
    'author' => $this->faker->lexify(str_repeat("?", 200)),
    'publisher' => $this->faker->lexify(str_repeat("?", 100)),
    'year_published' => $this->faker->numberBetween(1, 1000),
    'ISBN' => $this->faker->lexify(str_repeat("?", 100)),
    'expiry_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'textbook_dimension_id' => \App\Models\TextbookDimensions::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
