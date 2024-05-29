<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocaleContentTranslations extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "locale_content_translations";


    public function localeContents()
    {
        return $this->belongsTo(LocaleContents::class, 'locale_content_id', 'id');
    }


    public function locales()
    {
        return $this->belongsTo(Locales::class, 'locale_id', 'id');
    }
}
