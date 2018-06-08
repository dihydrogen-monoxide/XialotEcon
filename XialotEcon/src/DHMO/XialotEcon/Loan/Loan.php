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

namespace DHMO\XialotEcon\Loan;

use DHMO\XialotEcon\Account\Account;
use DHMO\XialotEcon\Currency\Currency;
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use poggit\libasynql\DataConnector;
use function time;

class Loan extends DataModel{
	public const DATUM_TYPE = "xialotecon.loan";

	/** @var Account */
	private $refAccount;
	/** @var Account|null */
	private $creditor;

	/** @var string */
	private $visibleName;
	/** @var float */
	private $minReturn;
	/** @var float */
	private $normalInterest;
	/** @var int */
	private $normalPeriod;
	/** @var float */
	private $principal;

	/** @var float */
	private $localReturn;
	/** @var float */
	private $nextInterest;
	/** @var int */
	private $nextCompoundDate;

	public static function createNew(DataModelCache $cache, string $debtorType, string $debtorName, string $loanType, ?Account $creditor, string $visibleName, float $minReturn, float $firstInterest, float $normalInterest, int $firstPeriod, int $normalPeriod, float $principal, Currency $currency, callable $onComplete) : void{
		Account::create($cache, $loanType, $debtorType, $debtorName, $currency, $principal, function(Account $refAccount) use ($principal, $visibleName, $creditor, $cache, $minReturn, $firstInterest, $normalInterest, $firstPeriod, $normalPeriod, $onComplete){
			$loan = new Loan($cache, self::DATUM_TYPE, self::generateXoid(self::DATUM_TYPE), true);
			$loan->refAccount = $refAccount;
			$loan->creditor = $creditor;
			$loan->visibleName = $visibleName;
			$loan->minReturn = $minReturn;
			$loan->normalInterest = $normalInterest;
			$loan->normalPeriod = $normalPeriod;
			$loan->principal = $principal;
			$loan->localReturn = 0.0;
			$loan->nextInterest = $firstInterest;
			$loan->nextCompoundDate = time() + $firstPeriod;
			$onComplete($loan);
		});
	}

	public function isGarbage() : bool{
		return $this->refAccount->isGarbage() || ($this->creditor !== null && $this->creditor->isGarbage()) || parent::isGarbage();
	}

	public function touchAccess() : void{
		parent::touchAccess();
		if($this->refAccount !== null){
			$this->refAccount->touchAccess();
		}
		if($this->creditor !== null){
			$this->creditor->touchAccess();
		}
	}

	protected function downloadChanges(DataModelCache $cache) : void{
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_LOAN_SINGLE_SELECT, [
			"loanId" => $this->getXoid(),
		], function(array $rows) use ($cache){
			$this->applyRow($cache, $rows[0]);
			$this->onChangesDownloaded();
		});
	}

	private function applyRow(DataModelCache $cache, array $row) : void{
		$this->refAccount = $cache->getAccount($row["accountId"]);
		$this->creditor = $row["creditor"] !== null ? $cache->getAccount($row["creditor"]) : null;

		$this->visibleName = $row["visibleName"];
		$this->minReturn = $row["minReturn"];
		$this->normalInterest = $row["normalInterest"];
		$this->normalPeriod = $row["normalPeriod"];
		$this->principal = $row["principal"];

		$this->localReturn = $row["localReturn"];
		$this->nextInterest = $row["nextInterest"];
		$this->nextCompoundDate = $row["nextCompoundDate"];
	}

	protected function uploadChanges(DataConnector $connector, bool $insert, callable $onComplete) : void{
		if($insert){
			$changeFunction = function() use ($onComplete, $connector){
				$connector->executeChange(Queries::XIALOTECON_LOAN_SINGLE_INSERT, [
					"loanId" => $this->getXoid(),
					"accountId" => $this->refAccount->getXoid(),
					"creditor" => $this->creditor !== null ? $this->creditor->getXoid() : null,

					"visibleName" => $this->visibleName,
					"minReturn" => $this->minReturn,
					"normalInterest" => $this->normalInterest,
					"normalPeriod" => $this->normalPeriod,
					"principal" => $this->principal,

					"localReturn" => $this->localReturn,
					"nextInterest" => $this->nextInterest,
					"nextCompoundDate" => $this->nextCompoundDate,
				], $onComplete);
			};
		}else{
			$changeFunction = function() use ($connector, $onComplete){
				$connector->executeChange(Queries::XIALOTECON_LOAN_SINGLE_UPDATE, [
					"loanId" => $this->getXoid(),
					"accountId" => $this->refAccount->getXoid(),
					"creditor" => $this->creditor !== null ? $this->creditor->getXoid() : null,

					"visibleName" => $this->visibleName,
					"minReturn" => $this->minReturn,
					"normalInterest" => $this->normalInterest,
					"normalPeriod" => $this->normalPeriod,
					"principal" => $this->principal,

					"localReturn" => $this->localReturn,
					"nextInterest" => $this->nextInterest,
					"nextCompoundDate" => $this->nextCompoundDate,
				], $onComplete);
			};
		}
		$this->refAccount->executeAfterNextUpload($this->creditor !== null ? function() use ($changeFunction){
			$this->creditor->executeAfterNextUpload($changeFunction);
		} : $changeFunction);
	}

	public function jsonSerialize() : array{
		return parent::jsonSerialize() + [
				"refAccount" => $this->refAccount->getXoid(),
				"creditor" => $this->creditor !== null ? $this->creditor->getXoid() : null,
				"visibleName" => $this->visibleName,
				"minReturn" => $this->minReturn,
				"normalInterest" => $this->normalInterest,
				"normalPeriod" => $this->normalPeriod,
				"principal" => $this->principal,
				"localReturn" => $this->localReturn,
				"nextInterest" => $this->nextInterest,
				"nextCompoundDate" => $this->nextCompoundDate,
			];
	}


	public function getRefAccount() : Account{
		$this->touchAccess();
		return $this->refAccount;
	}

	public function getCreditor() : ?Account{
		$this->touchAccess();
		return $this->creditor;
	}

	public function setCreditor(?Account $creditor) : void{
		$this->touchAutosave();
		$this->creditor = $creditor;
	}

	public function getVisibleName() : string{
		$this->touchAccess();
		return $this->visibleName;
	}

	public function setVisibleName(string $visibleName) : void{
		$this->touchAutosave();
		$this->visibleName = $visibleName;
	}

	public function getMinReturn() : float{
		$this->touchAccess();
		return $this->minReturn;
	}

	public function setMinReturn(float $minReturn) : void{
		$this->touchAutosave();
		$this->minReturn = $minReturn;
	}

	public function getNormalInterest() : float{
		$this->touchAccess();
		return $this->normalInterest;
	}

	public function setNormalInterest(float $normalInterest) : void{
		$this->touchAutosave();
		$this->normalInterest = $normalInterest;
	}

	public function getNormalPeriod() : int{
		$this->touchAccess();
		return $this->normalPeriod;
	}

	public function setNormalPeriod(int $normalPeriod) : void{
		$this->touchAutosave();
		$this->normalPeriod = $normalPeriod;
	}

	public function getPrincipal() : float{
		$this->touchAccess();
		return $this->principal;
	}

	public function setPrincipal(float $principal) : void{
		$this->touchAutosave();
		$this->principal = $principal;
	}

	public function getLocalReturn() : float{
		$this->touchAccess();
		return $this->localReturn;
	}

	public function setLocalReturn(float $localReturn) : void{
		$this->touchAutosave();
		$this->localReturn = $localReturn;
	}

	public function getNextInterest() : float{
		$this->touchAccess();
		return $this->nextInterest;
	}

	public function setNextInterest(float $nextInterest) : void{
		$this->touchAutosave();
		$this->nextInterest = $nextInterest;
	}

	public function getNextCompoundDate() : int{
		$this->touchAccess();
		return $this->nextCompoundDate;
	}

	public function setNextCompoundDate(int $nextCompoundDate) : void{
		$this->touchAutosave();
		$this->nextCompoundDate = $nextCompoundDate;
	}
}
