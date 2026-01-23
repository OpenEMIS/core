<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaAdministrativeLevels extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['id', 'name', 'level', 'area_administrative_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'area_administrative_id', 'modified_user_id', 'created_user_id'];
    // ✅ Disable Laravel's default timestamps
    protected $dates = ['modified', 'created'];
    protected $table = "area_administrative_levels";









    private function emptyFunction()
    {
        return;
    }
}
