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

class Google_Service_Storage_ComposeRequest extends Google_Collection
{
  protected $collection_key = 'sourceObjects';
  protected $destinationType = 'Google_Service_Storage_StorageObject';
  protected $destinationDataType = '';
  public $kind;
  protected $sourceObjectsType = 'Google_Service_Storage_ComposeRequestSourceObjects';
  protected $sourceObjectsDataType = 'array';

  public function setDestination(Google_Service_Storage_StorageObject $destination)
  {
    $this->destination = $destination;
  }
  public function getDestination()
  {
    return $this->destination;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSourceObjects($sourceObjects)
  {
    $this->sourceObjects = $sourceObjects;
  }
  public function getSourceObjects()
  {
    return $this->sourceObjects;
  }
}
