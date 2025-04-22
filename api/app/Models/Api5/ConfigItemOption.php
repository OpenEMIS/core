<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigItemOption extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "config_item_options";


    public function items()
    {
        return $this->belongsToMany(ConfigItem::class, 'option_type', 'option_type');
    }

}
