<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Api5\UserIdentities as Api5UserIdentities;
use Illuminate\Support\Facades\Log;

class UserIdentities extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'identity_type_id', 'number', 'issue_date', 'expiry_date', 'issue_location', 'nationality_id', 'comments', 'preferred', 'security_user_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'identity_type_id', 'nationality_id', 'security_user_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "user_identities";

    //POCOR-9590: same identity-lock as the v5 model — number / identity_type_id immutable on
    //rows whose identity_type is an external-source lookup key. See Api5\UserIdentities for
    //the shared isExternalLookupIdentityType() helper.
    protected static function booted(): void
    {
        static::updating(function (self $row) {
            if (!$row->isDirty('number') && !$row->isDirty('identity_type_id')) {
                return;
            }
            $typeId = (int) $row->getOriginal('identity_type_id');
            if (!Api5UserIdentities::isExternalLookupIdentityType($typeId)) {
                return;
            }
            Log::warning('POCOR-9590: v4 blocked update to external-lookup identity row', [
                'row_id'  => $row->id,
                'type_id' => $typeId,
                'fields'  => array_keys($row->getDirty()),
            ]);
            return false;
        });
    }





    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'security_user_id', 'id');
    }
}
