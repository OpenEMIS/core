<?php
/*
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

class Google_Service_AdExchangeBuyer_TargetingValue extends Google_Model
{
  protected $creativeSizeValueType = 'Google_Service_AdExchangeBuyer_TargetingValueCreativeSize';
  protected $creativeSizeValueDataType = '';
  protected $dayPartTargetingValueType = 'Google_Service_AdExchangeBuyer_TargetingValueDayPartTargeting';
  protected $dayPartTargetingValueDataType = '';
  public $longValue;
  public $stringValue;

  public function setCreativeSizeValue(Google_Service_AdExchangeBuyer_TargetingValueCreativeSize $creativeSizeValue)
  {
    $this->creativeSizeValue = $creativeSizeValue;
  }
  public function getCreativeSizeValue()
  {
    return $this->creativeSizeValue;
  }
  public function setDayPartTargetingValue(Google_Service_AdExchangeBuyer_TargetingValueDayPartTargeting $dayPartTargetingValue)
  {
    $this->dayPartTargetingValue = $dayPartTargetingValue;
  }
  public function getDayPartTargetingValue()
  {
    return $this->dayPartTargetingValue;
  }
  public function setLongValue($longValue)
  {
    $this->longValue = $longValue;
  }
  public function getLongValue()
  {
    return $this->longValue;
  }
  public function setStringValue($stringValue)
  {
    $this->stringValue = $stringValue;
  }
  public function getStringValue()
  {
    return $this->stringValue;
  }
}
