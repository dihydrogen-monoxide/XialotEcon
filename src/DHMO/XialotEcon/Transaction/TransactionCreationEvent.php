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

namespace DHMO\XialotEcon\Transaction;

use BadMethodCallException;
use DHMO\XialotEcon\Account\Account;
use DHMO\XialotEcon\UserException;
use DHMO\XialotEcon\XialotEconEvent;
use pocketmine\event\Cancellable;

class TransactionCreationEvent extends XialotEconEvent implements Cancellable{
	/** @var Account */
	private $source;
	/** @var Account */
	private $target;
	/** @var float */
	private $sourceReduction;
	/** @var float */
	private $targetAddition;
	/** @var string */
	private $transactionType;

	/** @var UserException|null */
	private $cancellation = null;

	public function __construct(Account $source, Account $target, float $sourceReduction, float $targetAddition, string $transactionType){
		parent::__construct();
		$this->source = $source;
		$this->target = $target;
		$this->sourceReduction = $sourceReduction;
		$this->targetAddition = $targetAddition;
		$this->transactionType = $transactionType;
	}

	public function getSource() : Account{
		return $this->source;
	}

	public function getTarget() : Account{
		return $this->target;
	}

	public function getSourceReduction() : float{
		return $this->sourceReduction;
	}

	public function getTargetAddition() : float{
		return $this->targetAddition;
	}

	public function getTransactionType() : string{
		return $this->transactionType;
	}

	public function setCancelled(bool $value = true) : void{
		if($value){
			throw new BadMethodCallException("Use TransactionCreationEvent->cancel() instead");
		}
		parent::setCancelled(false);
	}

	public function cancel(UserException $cancellation){
		$this->cancellation = $cancellation;
		parent::setCancelled();
	}

	public function uncancel() : void{
		parent::setCancelled(false);
	}

	public function getCancellation() : ?UserException{
		return $this->cancellation;
	}
}
