<?php  
//App::import('Core', 'Controller'); 
//App::import('Component', 'Email'); 
App::uses('AppTask', 'Console/Command/Task');
class CommonTask extends AppTask {
	public $limit = 1000;
	
	
	/**************************
	 * Common Helper functions
	 **************************/

    public function translateArrayValues(&$array, $delimiter=null) {
        if (!is_null($delimiter)) {
            foreach($array as $k => $v) {
                $array[$k] = __($this->addDelimiter($v, $delimiter)); 
            }    
        } else {
            foreach($array as $k => $v) {
                $array[$k] = __($v); 
            }
        }
    }

	public function translate(&$string){
		return __($string);
	}

    // convert uppercase character to space/whatever delimiter is set e.g. "InstitutionName" to "Institution Name"
    // the first character will be ignored
    public function addDelimiter($word, $delimiter) {
        $wordSize = strlen($word);
        $newWord = "";
        for ($i=0; $i < $wordSize; $i++) {
            if ($i > 1 && preg_match("/^[A-Z]$/", $word[$i])) {
                $newWord .= $delimiter.$word[$i];
            } else {
                $newWord .= $word[$i];    
            }
        }
        return $newWord;
    }

    public function cleanContent($str){
        $str = str_replace("&", "&amp;", $str);
		$str = str_replace("'", "&#39", $str);
        return $str = str_replace(",", "&#44", $str);
        // return $str = str_replace(",", "','", $str);
    }
	
    public function formatData(&$data){
        foreach($data as $k => &$arrv){
                foreach ($arrv as $key => $value) {
                        if(is_array($value)){
                                $arrv = array_merge($arrv,$value);
                                unset($data[$k][$key]);
                        }
                }
        }
    }

    public function formatResult(&$list) {
        $result = array();
        foreach($list as $record) {
            $data = array();
            foreach($record as $model => $val) {
                $data = array_merge($data, $val);
            }
            $result[] = $data;
        }
        return $result;
    }
	
    //Paths
    public function getReportWebRootPath(){
        //return ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS;
        return APP.WEBROOT_DIR.DS;
    }
    
    public function getLogPath(){
        $path = $this->getReportWebRootPath().'logs/reports/';
        if(!is_dir($path)){
            mkdir($this->getReportWebRootPath().'logs/reports/');
        }
        return $path;
    }
	
    public function getResultPath(){
		return $this->getReportWebRootPath().'reports'.DS;
	}
	
	
	public function createLog($filename,$content){
		$fp = fopen($filename, 'a+');
		fputs ($fp, $content);		
		fclose ($fp);
    }
	
    public function updateStatus($id,$status = 1){
        $this->BatchProcess->id = $id; 
		$cond = array('status'=>$status,'id'=>$id);
		if($status == 2){
			$cond = array_merge($cond,array('start_date'=>date('Y-m-d H:i:s')));
		}elseif($status == 3 ){
			$cond = array_merge($cond,array('finish_date'=>date('Y-m-d H:i:s')));
		}
        $this->BatchProcess->save(array('BatchProcess' => $cond));// set status to processing
	}
	
    public function getCount($id){
        $this->autoRender = false;
        $res = $this->Report->find('first',array('conditions'=>array('id'=>$id)));
		pr($res);
        //$s ='/SELECT(.*)FROM/s';
        //$r = 'SELECT count(*) as count FROM';
        //$countSql = preg_replace($s,$r,$res['BatchReport'][0]['query']);
        //$countRes = $this->Report->query($countSql);
        //echo json_encode(array('total'=>((isset($countRes[0][0]['count']))?$countRes[0][0]['count']:0), 'limit'=>$this->limit));
        $sql = $res['BatchReport'][0]['query'];
        if (!$this->checkandFormatCustomCount($sql)) {
            $sql = str_replace(',{cond}','',$sql);
            $sql = str_replace("'all'","'count');//",$sql);
        }
        $countSql = $sql;
        eval($countSql);
        return array('total'=>((isset($data))?$data:0), 'limit'=>$this->limit);
    }

    
    public function checkandFormatCustomCount(&$sql){
        if (preg_match('/join/i', $sql)) {
            $sql = preg_replace('/find\(\s*[\'|"]*all[\'|"]*/','find(\'count\'',$sql);//swap 'all' to 'count'
            $sql = preg_replace('/[\'|"]*fields[\'|"]*\s*\=\>\s*array\([^\)]*\)[,]*/','',$sql);//remove fields , fields specified in find will screw up count
            $sql = preg_replace('/[,]*\{cond\}\)\)\;/','));/*',$sql);//remove {cond}, comment rest of codeblock
            $sql = $sql . '*/';
            return true;
        } 
        return false;
    }

    
}

?>