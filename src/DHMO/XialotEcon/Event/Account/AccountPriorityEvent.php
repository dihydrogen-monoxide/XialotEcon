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
use function uksort;

class AccountPriorityEvent extends XialotEconEvent{
	/** @var AccountContributionEvent */
	private $event;
	/** @var int[] */
	private $priorities;

	public function __construct(AccountContributionEvent $event){
		parent::__construct();
		$this->event = $event;
		foreach($event->getAccounts() as $account){
			$this->priorities[$account->getUUID()->toString()] = 0;
		}
	}

	public function getEvent() : AccountContributionEvent{
		return $this->event;
	}

	public function getAccounts() : array{
		return $this->event->getAccounts();
	}

	public function applyPriority(string $uuid, int $modifier) : void{
		$this->priorities[$uuid] += $modifier;
	}

	/**
	 * @return Account[]
	 */
	public function sortResult() : array{
		$accounts = $this->event->getAccounts();
		uksort($accounts, function(string $u1, string $u2) : int{
			return $this->priorities[$u2] <=> $this->priorities[$u1]; // descending order
		});
		return $accounts;
	}
}
