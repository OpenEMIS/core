<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Areas extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'parent_id', 'lft', 'rght', 'area_level_id', 'order', 'visible', 'modified_user_id', 'modified', 'created_user_id', 'created', 'parent_id', 'area_level_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "areas";








    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }


    public function areaEducationChild()
    {
        return $this->children()->with('areaEducationChild');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }
}
