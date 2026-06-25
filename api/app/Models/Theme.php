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
        //POCOR-8851 starts
        if(isset($this->default_value) && !empty($value)){
            $value = base64_encode($value);
        }//POCOR-8851 ends
        return $value;
    }
}