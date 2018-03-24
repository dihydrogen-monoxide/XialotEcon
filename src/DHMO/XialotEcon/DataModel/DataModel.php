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

use InvalidStateException;
use pocketmine\Server;
use poggit\libasynql\DataConnector;
use function assert;
use function microtime;
use function strlen;
use function substr;

/**
 * Represents an abstract data model.
 *
 * A data model has three characteristics:
 * - Volatility: If this data model has not been accessed for a long time, it will be deleted from the {@link DataModelCache}.
 * - Invalidation: If data in this model have been updated remotely, this model will be first invalidated, and later updated. Data access is unavailable during this period. While this mechanism does not 100% eliminate concurrency changes, it effectively visualizes remote changes that the local server might keep overwriting.
 * - Autosaving: If data in this model have been updated locally, the changes will be asynchronously posted after a period of time. The frequency of auto-saving should be low enough to ensure that save queries are not in the wrong order such that the earlier save query (which is useless) overwrites the later save query.
 */
abstract class DataModel{
	/** @var DataModelTypeConfig[] */
	public static $CONFIG = [];

	// generic
	/** @var string */
	private $type;
	/** @var string */
	private $uuid;

	public static function generateUuid(string $type) : string{
		$crc32 = hash("crc32b", $type, true);
		$prefix = substr($crc32, 0, 2) ^ substr($crc32, 2);
		$hex = bin2hex($prefix . random_bytes(14));
		return
			substr($hex, 0, 4) . "-" .
			substr($hex, 4, 14) . "-" .
			substr($hex, 18, 14);
	}

	public function __construct(DataModelCache $cache, string $type, string $uuid, bool $needInsert){
		$this->type = $type;
		assert(strlen($uuid) === 34);
		$this->uuid = $uuid;

		$this->lastAccess = microtime(true);
		$this->lastAutosave = $needInsert ? 0.0 : microtime(true);

		$cache->trackModel($this);
	}

	public function getType() : string{
		return $this->type;
	}

	public function getUuid() : string{
		return $this->uuid;
	}

	// volatility

	/** @var float */
	private $lastAccess;
	/** @var bool */
	private $markedGarbage = false;

	public function touchAccess() : void{
		if($this->invalidated > 0){
			throw new InvalidStateException("Cannot access invalidated data model. Warp your accessors with DataModel->onValid() to ensure that they are only executed when the data model is valid.");
		}
		if($this->markedGarbage){
			throw new InvalidStateException("Cannot access garbage data model. Access the data model via the DataModelCache layer.");
		}
		$this->lastAccess = microtime(true);
	}

	public function isGarbage() : bool{
		return microtime(true) - $this->lastAccess > self::$CONFIG[$this->type]->garbageTimeout;
	}

	public function markGarbage(DataConnector $connector) : void{
		$this->markedGarbage = true;
		if($this->needAutosave){
			$this->mUploadChanges($connector, $this->lastAutosave === 0.0);
		}
	}

	// invalidation

	/** @var int */
	protected $invalidated = 0;

	/** @var callable[] */
	protected $onValidQueue = [];
	/** @var bool */
	protected $onValidConGuard = false;

	public function isValid() : bool{
		return $this->invalidated === 0;
	}

	public function onValid(callable $onValid) : void{
		if($this->invalidated === 0){
			$this->onValidConGuard = true;
			$onValid($this);
			$this->onValidConGuard = false;
			return;
		}
		$this->onValidQueue[] = $onValid;
	}

	public function remoteInvalidate(DataModelCache $cache) : void{
		$this->invalidated++;
		$this->downloadChanges($cache);
	}

	protected function onChangesDownloaded() : void{
		if($this->invalidated === 0){
			throw new InvalidStateException("Called onUpdateDownloaded() too many times");
		}
		$this->invalidated--;
		if($this->invalidated === 0){
			$this->onValidConGuard = true;
			foreach($this->onValidQueue as $call){
				$call($this);
			}
			$this->onValidQueue = [];
			$this->onValidConGuard = false;
		}
	}

	protected abstract function downloadChanges(DataModelCache $cache):void;

	// autosaving

	/** @var float */
	protected $lastAutosave;
	/** @var bool */
	protected $needAutosave = false;

	public function touchAutosave() : void{
		if($this->invalidated > 0){
			throw new InvalidStateException("Cannot update invalidated data model. Warp your accessors with DataModel->onValid() to ensure that they are only executed when the data model is valid.");
		}
		if($this->markedGarbage){
			throw new InvalidStateException("Cannot update garbage data model. Access the data model via the DataModelCache layer.");
		}
		$this->needAutosave = true;
	}

	public function checkAutosave(DataConnector $connector) : void{
		if($this->needAutosave && microtime(true) - $this->lastAutosave > self::$CONFIG[$this->type]->autoSavePeriod){
			$insert = $this->lastAutosave === 0.0;
			$this->lastAutosave = microtime(true);
			$this->needAutosave = false;
			$this->mUploadChanges($connector, $insert);
		}
	}

	private function mUploadChanges(DataConnector $connector, bool $insert) : void{
		if(self::$CONFIG[$this->type]->notifyChanges){
			$connector->executeInsert(Queries::XIALOTECON_CORE_PROVIDER_FEED_DATUM_UPDATE, [
				"server" => Server::getInstance()->getServerUniqueId(),
				"uuid" => $this->uuid
			]);
		}
		$this->uploadChanges($connector, $insert);
	}

	protected abstract function uploadChanges(DataConnector $connector, bool $insert):void;
}
