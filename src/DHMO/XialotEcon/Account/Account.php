<?php

/**
 * XialotEcon
 *
 * Copyright (C) 2017-2018 dihydrogen-monoxide
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace DHMO\XialotEcon\Account;

use DHMO\XialotEcon\Currency\Currency;
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use poggit\libasynql\DataConnector;
use poggit\libasynql\result\SqlSelectResult;

class Account extends DataModel{
	public const DATUM_TYPE = "xialotecon.account";

	/** @var string */
	protected $ownerType;
	/** @var string */
	protected $ownerName;
	/** @var string */
	protected $accountType;
	/** @var Currency */
	protected $currency;
	/** @var float */
	protected $balance;

	public static function create(DataModelCache $cache, string $type, string $ownerType, string $ownerName, Currency $currency, float $balance) : Account{
		$account = new Account($cache, self::DATUM_TYPE, self::generateUuid(self::DATUM_TYPE), true);
		$account->accountType = $type;
		$account->ownerType = $ownerType;
		$account->ownerName = $ownerName;
		$account->currency = $currency;
		$account->balance = $balance;
		return $account;
	}

	public static function getByUuid(DataModelCache $cache, string $uuid, callable $consumer) : void{
		if($cache->isTrackingModel($uuid)){
			$consumer($cache->getAccount($uuid));
			return;
		}
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_ACCOUNT_LOAD_BY_UUID, ["uuid" => $uuid], function(SqlSelectResult $result) use ($cache, $uuid, $consumer){
			if($cache->isTrackingModel($uuid)){
				$consumer($cache->getAccount($uuid));
				return;
			}
			if(empty($result->getRows())){
				$consumer(null);
				return;
			}
			$cache->getConnector()->executeChange(Queries::XIALOTECON_ACCOUNT_TOUCH, ["uuid" => $uuid]);
			$instance = new Account($cache, self::DATUM_TYPE, $uuid, false);
			$instance->applyRow($cache, $result->getRows()[0]);
			$consumer($instance);
		});
	}

	private function applyRow(DataModelCache $cache, array $row) : void{
		$this->ownerType = $row["ownerType"];
		$this->ownerName = $row["ownerName"];
		$this->accountType = $row["accountType"];
		$this->currency = $cache->getCurrency($row["currency"]);
		$this->balance = $row["balance"];
	}

	public function getOwnerType() : string{
		$this->touchAccess();
		return $this->ownerType;
	}

	public function getOwnerName() : string{
		$this->touchAccess();
		return $this->ownerName;
	}

	public function getAccountType() : string{
		$this->touchAccess();
		return $this->accountType;
	}

	public function getCurrency() : Currency{
		$this->touchAccess();
		return $this->currency;
	}

	public function getBalance() : float{
		$this->touchAccess();
		return $this->balance;
	}

	public function setOwnerType(string $ownerType) : void{
		$this->touchAutosave();
		$this->ownerType = $ownerType;
	}

	public function setOwnerName(string $ownerName) : void{
		$this->touchAutosave();
		$this->ownerName = $ownerName;
	}

	public function setAccountType(string $accountType) : void{
		$this->touchAutosave();
		$this->accountType = $accountType;
	}

	public function setBalance(float $balance) : void{
		$this->touchAutosave();
		$this->balance = $balance;
	}

	protected function uploadChanges(DataConnector $connector, bool $insert, callable $onComplete) : void{
		$connector->executeChange(Queries::XIALOTECON_ACCOUNT_UPDATE_HYBRID, [
			"uuid" => $this->getUuid(),
			"ownerType" => $this->ownerType,
			"ownerName" => $this->ownerName,
			"accountType" => $this->accountType,
			"currency" => $this->currency->getUuid(),
			"balance" => $this->balance,
		], $onComplete);
	}

	protected function downloadChanges(DataModelCache $cache) : void{
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_ACCOUNT_LOAD_BY_UUID, ["uuid" => $this->getUuid()], function(SqlSelectResult $result) use ($cache){
			$this->applyRow($cache, $result->getRows()[0]);
			$this->onChangesDownloaded();
		});
	}
}
