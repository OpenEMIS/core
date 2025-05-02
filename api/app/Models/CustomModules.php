<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomModules extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'model', 'visible', 'parent_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'parent_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "custom_modules";








private function emptyFunction() { return; }
}
