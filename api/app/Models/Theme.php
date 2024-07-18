<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasFactory;
    protected $table = "themes";

    public function getdefaultContentAttribute($value)
    {
        if(isset($this->default_value)){
            $value = base64_encode($value, true);
        }

        return $value;
    }
}