<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\TextbookConditions;
use App\Models\TextbookDimensions;
use App\Models\Textbooks;
use App\Models\TextbookStatuses;
use App\Models\InstitutionTextbooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use JWTAuth;


class TextbookRepository extends Controller
{

    public function getTextbookConditions(){
        
        try {
            $data = TextbookConditions::get();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Conditions Not Found');
        }
    }

    public function getTextbookByID($id){
        
        try {
            $data = Textbooks::where('id', $id)->first();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Not Found');
        }
    }

    public function getTextbookStatuses(){
        
        try {
            $data = TextbookStatuses::get();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Statuses Not Found');
        }
    }

    public function getTextbookDimensions(){
        
        try {
            $data = TextbookDimensions::get();
            return $data;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Textbook Dimensions Not Found');
        }
    }

    public function addTextbooks($request){
        
        DB::beginTransaction();

        try {
            $data = $request->all();
            
            $store['code'] = $data['code']??Null;
            $store['title'] = $data['title'];
            $store['author'] = $data['author']??Null;
            $store['publisher'] = $data['publisher']??Null;
            $store['year_published'] = $data['year_published'];
            $store['ISBN'] = $data['ISBN']??Null;
            $store['expiry_date'] = $data['expiry_date']??Null;
            $store['academic_period_id'] = $data['academic_period_id'];
            $store['education_grade_id'] = $data['education_grade_id'];
            $store['education_subject_id'] = $data['education_subject_id'];
            $store['textbook_dimension_id'] = $data['dimension_id']??Null;
            $store['created_user_id'] = JWTAuth::user()->id;
            $store['created'] = Carbon::now()->toDateTimeString();
            
            $insert = Textbooks::insert($store);
            DB::commit();
            return 1;

        }
        catch(\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add Textbook.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to add Textbook.');
        }
    }

    public function getInstitutionTextbookdata(int $institutionId, int $textbookId)
    {
        try {

            //For POCOR-7772 Start
            $permissions = checkAccess();
            
            if(isset($permissions)){
                if($permissions['userId'] > 2){
                    $institution_Ids = $permissions['institutionIds'];
                }
            }
            //For POCOR-7772 End

            $institutionTextbook = InstitutionTextbooks::where([
                'institution_id'=> $institutionId,
                 'textbook_id' => $textbookId
                 ]);

            //For POCOR-7772 Start
            if(isset($institution_Ids)){
                $institutionTextbook = $institutionTextbook->whereIn('institution_textbooks.institution_id', $institution_Ids);
            }
            //For POCOR-7772 End

            $institutionTextbook = $institutionTextbook->first();

            return $institutionTextbook;
            
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Textbook Data Not Found');
        }
    }

    public function addInstitutionTextbooks($request, $institutionId){
        
        DB::beginTransaction();

        try {
            $data = $request->all();

            $store['code'] = $data['code']??NULL;
            $store['comment'] = $data['comment']??NULL;
            $store['textbook_status_id'] = $data['textbook_status_id']??NULL;
            $store['textbook_condition_id'] = $data['textbook_condition_id']??NULL;
            $store['institution_id'] = $institutionId;
            $store['academic_period_id'] = $data['academic_period_id'];
            $store['education_grade_id'] = $data['education_grade_id'];
            $store['education_subject_id'] = $data['education_subject_id'];
            $store['security_user_id'] = $data['security_user_id']??NULL;
            $store['textbook_id'] = $data['textbook_id'];
            $store['created_user_id'] = JWTAuth::user()->id;
            $store['created'] = Carbon::now()->toDateTimeString();

            $insert = InstitutionTextbooks::insert($store);
            DB::commit();
            return 1;

        }
        catch(\Exception $e) {
            DB::rollback();
            Log::error(
                'Failed to add Institution Textbook.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
            return $this->sendErrorResponse('Failed to add Institution Textbook.');
        }
    }

}