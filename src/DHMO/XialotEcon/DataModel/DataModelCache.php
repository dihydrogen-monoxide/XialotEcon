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

namespace DHMO\XialotEcon\DataModel;

use DHMO\XialotEcon\Account\Account;
use DHMO\XialotEcon\Currency\Currency;
use DHMO\XialotEcon\Transaction\Transaction;
use InvalidArgumentException;
use poggit\libasynql\DataConnector;
use function assert;

class DataModelCache{
	/** @var DataConnector */
	private $connector;

	/** @var DataModel[] */
	private $models = [];

	public function __construct(DataConnector $connector){
		$this->connector = $connector;
	}

	public function getConnector() : DataConnector{
		return $this->connector;
	}

	public function onModelUpdated(string $uuid) : void{
		if(isset($this->models[$uuid])){
			$this->models[$uuid]->remoteInvalidate($this);
		}
	}

	public function trackModel(DataModel $model) : void{
		if(isset($this->models[$model->getUuid()])){
			throw new InvalidArgumentException("The data model $model is already being checked.");
		}
		$this->models[$model->getUuid()] = $model;
	}

	public function doCycle() : void{
		foreach($this->models as $model){
			if($model->isGarbage()){
				$model->markGarbage($this->connector);
				unset($this->models[$model->getUuid()]);
			}else{
				$model->checkAutosave($this->connector);
			}
		}
	}

	public function close() : void{
		foreach($this->models as $model){
			$model->markGarbage($this->connector);
		}
		$this->models = [];
	}

	public function isTrackingModel(string $uuid) : bool{
		return isset($this->models[$uuid]);
	}

	public function getModel(string $uuid) : ?DataModel{
		if(isset($this->models[$uuid])){
			$this->models[$uuid]->touchAccess();
			unset($this->models[$uuid]);
		}
		return null;
	}


	public function getCurrency(string $uuid) : Currency{
		$currency = $this->models[$uuid] ?? null;
		assert($currency !== null, "Currency $uuid is not loaded");
		assert($currency instanceof Currency, "UUID $uuid does not point to a Currency");
		$currency->touchAccess();
		return $currency;
	}

	public function getAccount(string $uuid) : ?Account{
		$account = $this->models[$uuid] ?? null;
		assert($account === null || $account instanceof Account, "UUID $uuid is not loaded or does not point to an Account");
		$account->touchAccess();
		return $account;
	}

	public function getTransaction(string $uuid) : ?Transaction{
		$transaction = $this->models[$uuid] ?? null;
		assert($transaction === null || $transaction instanceof Transaction, "UUID $uuid is not loaded or does not point to a Transaction");
		$transaction->touchAccess();
		return $transaction;
	}
}
