<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffStatuses extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name'];

    public $timestamps = false;
    protected $table = "staff_statuses";








private function emptyFunction() { return; }
}
