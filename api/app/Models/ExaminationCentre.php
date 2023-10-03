<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationCentre extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function institution()
    {
        return $this->belongsTo(Institutions::class);
    }

    public function area()
    {
        return $this->belongsTo(Areas::class);
    }

}