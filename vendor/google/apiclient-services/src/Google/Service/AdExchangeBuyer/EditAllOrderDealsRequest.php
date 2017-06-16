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

class Google_Service_AdExchangeBuyer_EditAllOrderDealsRequest extends Google_Collection
{
  protected $collection_key = 'deals';
  protected $dealsType = 'Google_Service_AdExchangeBuyer_MarketplaceDeal';
  protected $dealsDataType = 'array';
  protected $proposalType = 'Google_Service_AdExchangeBuyer_Proposal';
  protected $proposalDataType = '';
  public $proposalRevisionNumber;
  public $updateAction;

  public function setDeals($deals)
  {
    $this->deals = $deals;
  }
  public function getDeals()
  {
    return $this->deals;
  }
  public function setProposal(Google_Service_AdExchangeBuyer_Proposal $proposal)
  {
    $this->proposal = $proposal;
  }
  public function getProposal()
  {
    return $this->proposal;
  }
  public function setProposalRevisionNumber($proposalRevisionNumber)
  {
    $this->proposalRevisionNumber = $proposalRevisionNumber;
  }
  public function getProposalRevisionNumber()
  {
    return $this->proposalRevisionNumber;
  }
  public function setUpdateAction($updateAction)
  {
    $this->updateAction = $updateAction;
  }
  public function getUpdateAction()
  {
    return $this->updateAction;
  }
}
