<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\TextbookConditions;
use App\Models\TextbookDimensions;
use App\Models\Textbooks;
use App\Models\TextbookStatuses;
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
            // $store['dimension_id'] = $data['dimension_id'];
            $store['modified_user_id'] = $data['modified_user_id'];
            $store['modified'] = $data['modified'];
            $store['created_user_id'] = JWTAuth::user()->id;
            $store['created'] = Carbon::now()->toDateTimeString();

            // dd($store);
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


}