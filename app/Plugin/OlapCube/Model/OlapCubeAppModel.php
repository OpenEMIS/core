<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/
App::uses('AppModel', 'Model');
class OlapCubeAppModel extends AppModel {

    public function subquery($type, $options = null, $alias = null){ 
        $fields = array(); 
        if(is_string($type)){ 
            $isString = true; 
        }else{ 
            $alias = $options; 
            $options = $type; 
        } 
         
        if($alias === null){ 
            $alias = $this->alias . '2'; 
        } 
         
        if(isset($isString)){ 
            switch ($type){ 
                case 'count': 
                    $fields = array('COUNT(*)'); 
                    break; 
            } 
        } 
         
        $dbo = $this->getDataSource(); 
                 
        $default = array( 
            'fields' => $fields, 
            'table' => $dbo->fullTableName($this), 
            'alias' => $alias, 
            'limit' => null, 
            'offset' => null, 
            'joins' => array(), 
            'conditions' => array(), 
            'order' => null, 
            'group' => null 
        ); 
         
        $params = array_merge($default, $options); 
        $subQuery = $dbo->buildStatement($params, $this); 
         
        return $subQuery; 
    }

}
