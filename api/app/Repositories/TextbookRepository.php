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

            $store['code'] = $data['code'];
            $store['title'] = $data['title'];
            $store['author'] = $data['author'];
            $store['publisher'] = $data['publisher'];
            $store['year_published'] = $data['year_published'];
            $store['ISBN'] = $data['ISBN'];
            $store['expiry_date'] = $data['expiry_date'];
            $store['academic_period_id'] = $data['academic_period_id'];
            $store['education_grade_id'] = $data['education_grade_id'];
            $store['education_subject_id'] = $data['education_subject_id'];
            $store['dimension_id'] = $data['textbook_dimension_id'];
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
            $institutionTextbook = InstitutionTextbooks::where([
                'institution_id'=> $institutionId,
                 'textbook_id' => $textbookId
                 ])
                 ->first();

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

            $store['code'] = $data['code'];
            $store['comment'] = $data['comment'];
            $store['textbook_status_id'] = $data['textbook_status_id'];
            $store['textbook_condition_id'] = $data['textbook_condition_id'];
            $store['institution_id'] = $institutionId;
            $store['academic_period_id'] = $data['academic_period_id'];
            $store['education_grade_id'] = $data['education_grade_id'];
            $store['education_subject_id'] = $data['education_subject_id'];
            $store['security_user_id'] = $data['security_user_id'];
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