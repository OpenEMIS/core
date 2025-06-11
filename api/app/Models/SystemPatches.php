<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemPatches extends Model
{
    use HasFactory;

    protected $table = 'system_patches';

    // ✅ Allow mass assignment
    protected $fillable = ['issue', 'version', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = 'issue';
    protected $keyType = 'string';
    public $incrementing = false;


    // Override getKeyForSaveQuery to handle composite keys


    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }
}






