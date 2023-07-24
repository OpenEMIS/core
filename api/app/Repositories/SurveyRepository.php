<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\SurveyForms;
use App\Models\InstitutionSurveys;


define("NS_XHTML", "http://www.w3.org/1999/xhtml");
define("NS_XF", "http://www.w3.org/2002/xforms");
define("NS_EV", "http://www.w3.org/2001/xml-events");
define("NS_XSD", "http://www.w3.org/2001/XMLSchema");
define("NS_OE", "https://www.openemis.org");

class SurveyRepository extends Controller
{
    public function getSurveys($request)
    {
        try {
            $params = $request->all();

            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $surveys = InstitutionSurveys::with('surveyForms','surveyForms.customModule');

            if(isset($params['institution_id'])){
                $surveys = $surveys->where('institution_id', $params['institution_id']);
            }

            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $surveys = $surveys->orderBy($col, $orderBy);
            }

            $list = $surveys->paginate($limit)->toArray();
            
            return $list;
        } catch (\Exception $e) {
            
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Surveys List Not Found');
        }
    }


    public function downloadXform($request, $surveyFormId)
    {
        try {
            $param = $request->all();
            $surveyForm = SurveyForms::where('id', $surveyFormId)->first();
            /*$xml = new \DOMDocument("1.0", "UTF-8");

            // It will format the output in xml format otherwise
            // the output will be in a single row

            $xml->formatOutput=true;

            $fitness=$xml->createElement("users");
            $xml->appendChild($fitness);


            $user=$xml->createElement("user");
            $fitness->appendChild($user);

            $uid=$xml->createElement("uid", 1);
            $user->appendChild($uid);

            return $xml->saveXML();*/


            $xmlstr = '<?xml version="1.0" encoding="UTF-8"?>
                <html
                    xmlns="' . NS_XHTML . '"
                    xmlns:xf="' . NS_XF . '"
                    xmlns:ev="' . NS_EV . '"
                    xmlns:xsd="' . NS_XSD . '"
                    xmlns:oe="' . NS_OE . '">
                </html>';

            //Creating SimpleXML Object
            $xml = new \SimpleXMLElement($xmlstr);

            //Setting the newsPagePrefix attribute and its value to the news node
            //$newsXML->addAttribute('newsPagePrefix', 'Times of India');

            $headNode = $xml->addChild("head", null, NS_XHTML);
            $bodyNode = $xml->addChild("body", null, NS_XHTML);
            $headNode->addChild("title", $surveyForm->name, NS_XHTML);
            $metaNode = $headNode->addChild("meta", null, NS_XHTML);
            $metaNode->addAttribute("name", "description");
            $metaNode->addAttribute("content", $surveyForm->description);
            $modelNode = $headNode->addChild("model", null, NS_XF);

            $instanceNode = $modelNode->addChild("instance", null, NS_XF);
            $instanceNode->addAttribute("id", "xform");

            $formNode = $instanceNode->addChild('SurveyForms', null, NS_OE);
            $formNode->addAttribute("id", $surveyFormId);

            return $xml->saveXML();
        } catch (\Exception $e) {
            dd($e);
            Log::error(
                'Failed to download survey xform.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to download survey xform.');
        }
    }
}

