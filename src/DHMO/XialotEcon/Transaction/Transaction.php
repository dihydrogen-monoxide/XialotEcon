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

use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use InvalidStateException;
use poggit\libasynql\DataConnector;
use poggit\libasynql\result\SqlSelectResult;
use function time;

class Transaction extends DataModel{
	public const DATUM_TYPE = "xialotecon.transaction";

	/** @var string */
	protected $sourceUuid;
	/** @var string */
	protected $targetUuid;
	/** @var float */
	protected $sourceReduction;
	/** @var float */
	protected $targetAddition;
	/** @var string */
	protected $transactionType;
	/** @var int */
	protected $date;

	public static function createNew(DataModelCache $cache, TransactionCreationEvent $event) : Transaction{
		if($event->isCancelled()){
			throw new InvalidStateException("Cannot create transaction from a cancelled event");
		}
		$transaction = new Transaction($cache, Transaction::DATUM_TYPE, DataModel::generateUuid(Transaction::DATUM_TYPE), true);
		$transaction->sourceUuid = $event->getSource()->getUuid();
		$transaction->targetUuid = $event->getTarget()->getUuid();
		$transaction->sourceReduction = $event->getSourceReduction();
		$transaction->targetAddition = $event->getTargetAddition();
		$transaction->transactionType = $event->getTransactionType();
		$transaction->date = time();
		return $transaction;
	}

	public static function getByUuid(DataModelCache $cache, string $uuid, callable $consumer) : void{
		if($cache->isTrackingModel($uuid)){
			$consumer($cache->getTransaction($uuid));
			return;
		}
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_TRANSACTION_LOAD_BY_UUID, ["uuid" => $uuid], function(SqlSelectResult $result) use ($cache, $uuid, $consumer){
			if($cache->isTrackingModel($uuid)){
				$consumer($cache->getAccount($uuid));
				return;
			}
			if(empty($result->getRows())){
				$consumer(null);
				return;
			}
			$instance = new Transaction($cache, self::DATUM_TYPE, $uuid, false);
			$instance->applyRow($result->getRows()[0]);
			$consumer($instance);
		});
	}

	private function applyRow(array $row) : void{
		$this->sourceUuid = $row["source"];
		$this->targetUuid = $row["target"];
		$this->sourceReduction = $row["sourceReduction"];
		$this->targetAddition = $row["targetAddition"];
		$this->transactionType = $row["transactionType"];
		$this->date = $row["date"];
	}

	protected function downloadChanges(DataModelCache $cache) : void{
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_TRANSACTION_LOAD_BY_UUID, ["uuid" => $this->getUuid()], function(SqlSelectResult $result){
			$this->applyRow($result->getRows()[0]);
		});
	}

	protected function uploadChanges(DataConnector $connector, bool $insert, callable $onComplete) : void{
		$connector->executeInsert(Queries::XIALOTECON_TRANSACTION_UPDATE_HYBRID, [
			"uuid" => $this->getUuid(),
			"date" => $this->date,
			"transactionType" => $this->transactionType,
			"sourceReduction" => $this->sourceReduction,
			"targetAddition" => $this->targetAddition,
			"source" => $this->sourceUuid,
			"target" => $this->targetUuid,
		], $onComplete);
	}
}
