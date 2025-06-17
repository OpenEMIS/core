<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class SurveyRules extends Model
{
    use UuidId;

    use HasFactory;

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'survey_form_id', 'survey_question_id', 'dependent_question_id', 'show_options', 'enabled', 'modified', 'modified_user_id', 'created', 'created_user_id', 'survey_form_id', 'survey_question_id', 'dependent_question_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "survey_rules";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = "string";

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();

    }

}




