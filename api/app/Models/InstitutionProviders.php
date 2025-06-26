<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionProviders extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'order', 'visible', 'editable', 'default', 'institution_sector_id', 'international_code', 'national_code', 'modified_user_id', 'modified', 'created_user_id', 'created', 'institution_sector_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_providers";








private function emptyFunction() { return; }
}
