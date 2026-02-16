<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffCustomFields extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'description', 'field_type', 'is_mandatory', 'is_unique', 'params', 'modified_user_id', 'modified', 'created_user_id', 'created', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "staff_custom_fields";








    public function staffCustomFieldOption()
    {
        return $this->hasMany(StaffCustomFieldOption::class, 'staff_custom_field_id', 'id');
    }
}
