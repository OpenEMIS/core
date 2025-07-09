<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class InstitutionSurveyAnswers extends Model
{
    use HasFactory;
    use UuidId;

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'text_value', 'number_value', 'decimal_value', 'textarea_value', 'date_value', 'time_value', 'file', 'survey_question_id', 'institution_survey_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'survey_question_id', 'institution_survey_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $table = "institution_survey_answers";

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    public $incrementing = false;

    public $casts = [
        'id' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
    }
}






