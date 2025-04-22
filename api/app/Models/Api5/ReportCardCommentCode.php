<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCardCommentCode extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "report_card_comment_codes";
}
