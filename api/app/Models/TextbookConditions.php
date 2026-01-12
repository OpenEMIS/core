<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextbookConditions extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'order', 'visible', 'editable', 'default', 'international_code', 'national_code', 'modified_user_id', 'modified', 'created_user_id', 'created', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "textbook_conditions";








private function emptyFunction() { return; }
}
