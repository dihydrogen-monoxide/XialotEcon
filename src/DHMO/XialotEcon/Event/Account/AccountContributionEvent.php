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

namespace DHMO\XialotEcon\Event\Account;

use DHMO\XialotEcon\Account;
use DHMO\XialotEcon\Event\XialotEconEvent;

class AccountContributionEvent extends XialotEconEvent{
	public const FB_DEPOSIT = "xialotecon.core.account.isDeposit";
	public const FF_AMOUNT = "xialotecon.core.account.amount";
	public const FB_SELF_INITIATED = "xialotecon.core.account.initiator.self";
	public const FB_OTHER_INITIATED = "xialotecon.core.account.initiator.other";

	/** @var bool[] */
	private $booleanFlags = []; // an optional flag should set false as the neutral value
	/** @var int[] */
	private $intFlags = []; // an optional flag should set 0 as the neutral value
	/** @var float[] */
	private $floatFlags = []; // an optional flag should set 0.0 as the neutral value

	/** @var Account[] */
	private $accounts = [];

	public function flagBoolean(string $name, bool $value) : AccountContributionEvent{
		$this->booleanFlags[$name] = $value;
		return $this;
	}

	public function flagInt(string $name, int $value) : AccountContributionEvent{
		$this->intFlags[$name] = $value;
		return $this;
	}

	public function flagFloat(string $name, float $value) : AccountContributionEvent{
		$this->floatFlags[$name] = $value;
		return $this;
	}

	public function contributeAccount(Account $account) : void{
		$this->accounts[$account->getUUID()->toString()] = $account;
	}

	/**
	 * @return Account[]
	 */
	public function getAccounts() : array{
		return $this->accounts;
	}
}
