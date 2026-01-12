<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocaleContentTranslations extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'translation', 'locale_content_id', 'locale_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'locale_content_id', 'locale_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

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
