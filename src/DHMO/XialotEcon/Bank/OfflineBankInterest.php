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
use DHMO\XialotEcon\DataModel\DataModel;
use poggit\libasynql\DataConnector;
use function intdiv;
use function time;

abstract class OfflineBankInterest extends DataModel{
	/** @var Account */
	protected $account;
	/** @var float */
	protected $period;
	/** @var int */
	protected $lastApplied;

	/** @var callable[] */
	protected $afterNextApp = [];

	public function isGarbage() : bool{
		return $this->account->isGarbage() || parent::isGarbage();
	}

	public function tickCheck(DataConnector $connector) : void{
		parent::tickCheck($connector);
		if($this->account->isValid() && $this->isValid() && time() - $this->lastApplied >= $this->period){
			$intervals = intdiv(time() - $this->lastApplied, $this->period);
			$this->setLastApplied($this->lastApplied + (int) ($intervals * $this->period));
			$balance = $this->account->getBalance();
			$balance = $this->applyInterest($balance, $intervals);
			$this->account->setBalance($balance);
		}

		foreach($this->afterNextApp as $c){
			$c();
		}
		// NOTE Potential concurrency issue with afterNextApp
		$this->afterNextApp = [];
	}

	public function ensureApplyInterest(callable $complete) : void{
		if(time() - $this->lastApplied < $this->period){
			$complete();
			return;
		}

		$this->afterNextApp[] = $complete;
	}

	public abstract function applyInterest(float $balance, int $intervals) : float;

	protected function applyRow(Account $account, array $row) : void{
		$this->account = $account;
		$this->period = $row["period"];
		$this->lastApplied = $row["lastApplied"];
	}

	public function getPeriod() : float{
		$this->touchAccess();
		return $this->period;
	}

	public function getLastApplied() : int{
		$this->touchAccess();
		return $this->lastApplied;
	}

	public function setPeriod(float $period) : void{
		$this->period = $period;
		$this->touchAutosave();
	}

	public function setLastApplied(int $lastApplied) : void{
		$this->lastApplied = $lastApplied;
		$this->touchAutosave();
	}
}
