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
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\Transaction\Transaction;
use DHMO\XialotEcon\XialotEcon;
use InvalidArgumentException;
use poggit\libasynql\CallbackTask;
use poggit\libasynql\DataConnector;
use function assert;

final class DataModelCache{
	/** @var XialotEcon */
	private $plugin;
	/** @var DataConnector */
	private $connector;

	/** @var DataModel[] */
	private $models = [];

	/** @var void|int */
	private $lastUpdateTick;
	/** @var void|int */
	private $lastUpdateId;

	private $fetchUpdateTask;
	private $serverId;

	public function __construct(XialotEcon $plugin, DataConnector $connector){
		$this->plugin = $plugin;
		$this->connector = $connector;

		$this->fetchUpdateTask = new CallbackTask([$this, "fetchUpdate"]);
		$this->serverId = $this->plugin->getServer()->getServerUniqueId()->toString();
	}

	public function getPlugin() : XialotEcon{
		return $this->plugin;
	}

	public function scheduleUpdate(?callable $fetchFirst = null) : void{
		if(isset($this->lastUpdateId)){
			$currentTick = $this->plugin->getServer()->getTick();
			$remTicks = $this->lastUpdateTick + 20 - $currentTick;
			if($remTicks <= 0){
				$this->fetchUpdate();
			}else{
				$this->plugin->getScheduler()->scheduleDelayedTask($this->fetchUpdateTask, $remTicks);
			}
		}else{
			$this->lastUpdateTick = $this->plugin->getServer()->getTick();
			$this->connector->executeSelect(Queries::XIALOTECON_DATA_MODEL_FEED_FETCH_FIRST, [], function(array $rows) use ($fetchFirst){
				$this->lastUpdateId = $rows[0]["maxUpdateId"] ?? -1;
				if($fetchFirst !== null){
					$fetchFirst();
				}
				$this->scheduleUpdate();
			});
		}
	}

	public function fetchUpdate() : void{
		$this->lastUpdateTick = $this->plugin->getServer()->getTick();
		$this->connector->setLoggingQueries(false);
		$this->connector->executeSelect(Queries::XIALOTECON_DATA_MODEL_FEED_FETCH_NEXT, [
			"lastMaxUpdate" => $this->lastUpdateId,
			"server" => $this->serverId,
		], function(array $rows){
			$updates = [];
			foreach($rows as $row){
				if($this->lastUpdateId < $row["updateId"]){
					$this->lastUpdateId = $row["updateId"];
				}
				$updates[] = $row["xoid"];
			}
			foreach($updates as $update){
				$this->onModelUpdated($update);
			}
		});
		$this->connector->setLoggingQueries(true);
		$this->scheduleUpdate();
	}

	public function getConnector() : DataConnector{
		return $this->connector;
	}

	public function onModelUpdated(string $xoid) : void{
		if(isset($this->models[$xoid])){
			$this->models[$xoid]->remoteInvalidate($this);
		}
	}

	public function trackModel(DataModel $model) : void{
		if(isset($this->models[$model->getXoid()])){
			throw new InvalidArgumentException("The data model {$model->getXoid()} is already being checked.");
		}

		$this->models[$model->getXoid()] = $model;
	}

	public function doCycle() : void{
		foreach($this->models as $model){
			if($model->isGarbage()){
				$xoid = $model->getXoid(false);
				$model->markGarbage($this->connector);
				unset($this->models[$xoid]);
			}else{
				$model->tickCheck($this->connector);
			}
		}
	}

	public function close() : void{
		foreach($this->models as $model){
			$model->markGarbage($this->connector);
		}
		$this->models = [];
	}

	public function isTrackingModel(string $xoid) : bool{
		return isset($this->models[$xoid]);
	}

	public function getModel(string $xoid) : ?DataModel{
		if(isset($this->models[$xoid])){
			$this->models[$xoid]->touchAccess();
			unset($this->models[$xoid]);
		}
		return null;
	}


	public function getCurrency(string $xoid) : Currency{
		$currency = $this->models[$xoid] ?? null;
		assert($currency !== null, "Currency $xoid is not loaded");
		assert($currency instanceof Currency, "XOID $xoid does not point to a Currency");
		$currency->touchAccess();
		return $currency;
	}

	public function getAccount(string $xoid) : ?Account{
		$account = $this->models[$xoid] ?? null;
		assert($account === null || $account instanceof Account, "XOID $xoid is not loaded or does not point to an Account");
		$account->touchAccess();
		return $account;
	}

	public function getTransaction(string $xoid) : ?Transaction{
		$transaction = $this->models[$xoid] ?? null;
		assert($transaction === null || $transaction instanceof Transaction, "XOID $xoid is not loaded or does not point to a Transaction");
		$transaction->touchAccess();
		return $transaction;
	}

	/**
	 * @return DataModel[]
	 */
	public function getAll() : array{
		return $this->models;
	}
}
