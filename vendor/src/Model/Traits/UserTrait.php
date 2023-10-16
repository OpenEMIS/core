<?php
namespace App\Model\Traits;

trait UserTrait {

	protected function getNameDefaults() {
        /* To create option field for Administration to set these default values for system wide use */
        return array(
            'middle'    => true,
            'third'     => true,
            'preferred' => false
        );
    }

    protected function getNameKeys($otherNames=[]) {
        $defaults = $this->getNameDefaults();
        $middle = (isset($otherNames['middle'])&&is_bool($otherNames['middle'])&&$otherNames['middle']) ? $otherNames['middle'] : $defaults['middle'];
        $third = (isset($otherNames['third'])&&is_bool($otherNames['third'])&&$otherNames['third']) ? $otherNames['third'] : $defaults['third'];
        $preferred = (isset($otherNames['preferred'])&&is_bool($otherNames['preferred'])&&$otherNames['preferred']) ? $otherNames['preferred'] : $defaults['preferred'];
        return array(
            'first_name'    =>  true,
            'middle_name'   =>  $middle,
            'third_name'    =>  $third,
            'last_name'     =>  true,
            'preferred_name'=>  $preferred
        );
    }    

}
