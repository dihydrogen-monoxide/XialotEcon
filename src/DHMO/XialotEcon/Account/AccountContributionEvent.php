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

use DHMO\XialotEcon\XialotEconEvent;

class AccountContributionEvent extends XialotEconEvent{
	public const FB_WITHDRAWAL = "xialotecon.transaction.isWithdrawal";
	public const FB_DEPOSIT = "xialotecon.transaction.isDeposit";
	public const FF_AMOUNT = "xialotecon.transaction.amount";
	public const FB_SELF_INITIATED = "xialotecon.transaction.initiator.self";
	public const FB_OTHER_INITIATED = "xialotecon.transaction.initiator.other";
	public const FS_OWNER_TYPE = "xialotecon.account.owner.type";
	public const FS_OWNER_NAME = "xialotecon.account.owner.name";

	/** @var bool[] */
	private $booleanFlags = [];
	/** @var int[] */
	private $intFlags = [];
	/** @var float[] */
	private $floatFlags = [];
	/** @var string[] */
	private $stringFlags = [];

	/** @var Account[] */
	private $accounts = [];

	public static function build() : AccountContributionEvent{
		return new AccountContributionEvent();
	}

	private function __construct(){
		parent::__construct();
	}

	public function flagBoolean(string $name, bool $value) : AccountContributionEvent{
		$this->booleanFlags[$name] = $value;
		return $this;
	}

	public function checkBoolean(string $name, bool $default = false) : bool{
		return $this->booleanFlags[$name] ?? $default;
	}

	public function flagInt(string $name, int $value) : AccountContributionEvent{
		$this->intFlags[$name] = $value;
		return $this;
	}

	public function checkInt(string $name, int $default = 0) : int{
		return $this->intFlags[$name] ?? $default;
	}

	public function flagFloat(string $name, float $value) : AccountContributionEvent{
		$this->floatFlags[$name] = $value;
		return $this;
	}

	public function checkFloat(string $name, float $default = 0.0) : float{
		return $this->floatFlags[$name] ?? $default;
	}

	public function flagString(string $name, string $value) : AccountContributionEvent{
		$this->floatFlags[$name] = $value;
		return $this;
	}

	public function checkString(string $name, string $default = "") : string{
		return $this->stringFlags[$name] ?? $default;
	}

	public function contributeAccount(Account $account) : void{
		$this->accounts[$account->getUuid()] = $account;
	}

	/**
	 * @return Account[]
	 */
	public function getAccounts() : array{
		return $this->accounts;
	}
}
