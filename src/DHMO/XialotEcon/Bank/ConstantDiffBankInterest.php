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

namespace DHMO\XialotEcon\Bank;

use DHMO\XialotEcon\Account\Account;
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\DataModel\DataModelCache;
use poggit\libasynql\DataConnector;
use poggit\libasynql\result\SqlSelectResult;
use function time;

class ConstantDiffBankInterest extends OfflineBankInterest{
	public const DATUM_TYPE = "xialotecon.bank.interest.constant.diff";

	/** @var float */
	private $diff;

	public function applyInterest(float $balance, int $intervals) : float{
		return $balance + $intervals * $this->diff;
	}

	public static function createNew(DataModelCache $cache, Account $account, float $diff, float $period) : ConstantDiffBankInterest{
		$interest = new ConstantDiffBankInterest($cache, self::DATUM_TYPE, self::generateUuid(self::DATUM_TYPE), true);
		$interest->diff = $diff;
		$interest->account = $account;
		$interest->period = $period;
		$interest->lastApplied = time();
		return $interest;
	}

	public static function forAccount(DataModelCache $cache, Account $account, callable $consumer){
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_BANK_INTEREST_FIND_BY_ACCOUNT_CONSTANT_DIFF, [
			"accountId" => $account->getUuid()
		], function(SqlSelectResult $result) use ($consumer, $cache, $account){
			$interests = [];
			foreach($result->getRows() as $row){
				$interests[] = $interest = new ConstantDiffBankInterest($cache, self::DATUM_TYPE, $row["interestId"], false);
				$interest->applyRow($account, $row);
			}
			$consumer($interests);
		});
	}

	protected function applyRow(Account $account, array $row) : void{
		parent::applyRow($account, $row);
		$this->diff = $row["diff"];
	}

	public function getDiff() : float{
		$this->touchAccess();
		return $this->diff;
	}

	public function setDiff(float $diff) : void{
		$this->diff = $diff;
		$this->touchAutosave();
	}

	protected function downloadChanges(DataModelCache $cache) : void{
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_BANK_INTEREST_FIND_BY_UUID_CONSTANT_DIFF, [
			"interestId" => $this->getUuid(),
		], function(SqlSelectResult $result){
			$this->applyRow($this->account, $result->getRows()[0]);
			$this->onChangesDownloaded();
		});
	}

	protected function uploadChanges(DataConnector $connector, bool $insert, callable $onComplete) : void{
		$connector->executeChange($insert ?
			Queries::XIALOTECON_BANK_INTEREST_INSERT_CONSTANT_DIFF :
			Queries::XIALOTECON_BANK_INTEREST_UPDATE_CONSTANT_DIFF, [
			"interestId" => $this->getUuid(),
			"accountId" => $this->account->getUuid(),
			"diff" => $this->diff,
			"period" => $this->period,
			"lastApplied" => $this->lastApplied
		], $onComplete);
	}
}
