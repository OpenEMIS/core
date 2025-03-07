<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// POCOR-7394-S

class AbsenceReasons extends Model
{
    use HasFactory;
    protected $table = "student_absence_reasons";
    /**
     * @OA\Info(
     *     title="OpenEMIS Core API V5",
     *     description="The [OpenEMIS](https://www.openemis.org/) initiative aims to deploy a high-quality Education Management Information System (EMIS) designed to collect and report data on schools, students, teachers and staff. The system was conceived by `UNESCO` to be a royalty-free system that can be easily customized to meet the specific needs of member countries.",
     *     termsOfService="https://www.openemis.org/terms-of-service/",
     *     version="5.0.0",
     *      @OA\License(
     *          name="GNU General Public License V3.0",
     *          url="https://www.gnu.org/licenses/gpl-3.0.en.html"
     *      ),
     *      @OA\Contact(
     *          email="support@openemis.org"
     *      ),
     * ),
     * @OA\Server(
     *      url="https://demo.openemis.org/core"
     *  ),
     */
    public function _swaggerHelper() {
        return;
    }
}
