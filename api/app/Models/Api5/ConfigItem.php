<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigItem extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "config_items";

    public function itemOptions()
    {
        return $this->hasMany(ConfigItemOption::class, 'option_type', 'option_type')->orderBy('order', 'ASC');
    }
}
