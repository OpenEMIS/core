<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionSurveyTableCells extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    public $timestamps = false;
    public $incrementing = false;
// ✅ Disable Laravel's default timestamps
    protected $fillable = ['text_value', 'number_value', 'decimal_value', 'survey_question_id', 'survey_table_column_id', 'survey_table_row_id', 'institution_survey_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'survey_question_id', 'survey_table_column_id', 'survey_table_row_id', 'institution_survey_id', 'modified_user_id', 'created_user_id'];

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $table = "institution_survey_table_cells";

    // ✅ Define the primary key
    protected $dates = ['modified', 'created'];
    protected $primaryKey = ["survey_question_id", "survey_table_column_id", "survey_table_row_id", "institution_survey_id"];









    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    // Override setKeysForSaveQuery to handle composite keys
    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }


}
