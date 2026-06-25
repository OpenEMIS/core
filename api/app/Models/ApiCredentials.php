<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiCredentials extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['id', 'name', 'client_id', 'public_key', 'api_key', 'modified_user_id', 'modified', 'created_user_id', 'created', 'client_id', 'modified_user_id', 'created_user_id'];
    protected $dates = ['modified', 'created'];
    protected $table = "api_credentials";









    private function emptyFunction()
    {
        return;
    }
}
