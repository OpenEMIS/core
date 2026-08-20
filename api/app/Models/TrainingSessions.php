<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSessions extends Model
{
    use HasFactory;

    protected $table = 'training_sessions';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'start_date', 'end_date', 'comment', 'training_course_id', 'training_provider_id', 'assignee_id', 'status_id', 'area_id', 'training_center', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key


     // Override getKeyForSaveQuery to handle composite keys








    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if(!is_array($keyName)){
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
        if(!is_array($keyName)){
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'created':
                return __('Created');
            case 'created_user_id':
                    return __('Created User');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified User');

            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }


}
