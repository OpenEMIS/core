<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Areas extends Model
{
    use HasFactory;

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
