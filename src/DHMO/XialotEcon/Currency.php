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

namespace DHMO\XialotEcon;

use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use poggit\libasynql\DataConnector;
use poggit\libasynql\result\SqlSelectResult;

class Currency extends DataModel{
	public const DATUM_TYPE = "xialotecon.core.currency";

	/** @var string */
	private $name;
	/** @var string */
	private $symbolBefore;
	/** @var string */
	private $symbolAfter;

	public static function createNew(DataModelCache $cache, string $name, string $symbolBefore, string $symbolAfter) : Currency{
		$uuid = self::generateUuid(self::DATUM_TYPE);
		$instance = new Currency($cache, $uuid, self::DATUM_TYPE, true);
		$instance->name = $name;
		$instance->symbolBefore = $symbolBefore;
		$instance->symbolAfter = $symbolAfter;
		return $instance;
	}

	public static function loadAll(DataModelCache $cache, callable $consumer) : void{
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_CORE_CURRENCY_LOAD_ALL, [], function(SqlSelectResult $result) use ($consumer, $cache){
			$currencies = [];
			foreach($result->getRows() as $row){
				$uuid = $row["currencyId"];
				if($cache->isTrackingModel($uuid)){
					$currencies[] = $cache->getCurrency($uuid);
				}else{
					$instance = new Currency($cache, self::DATUM_TYPE, $uuid, false);
					$instance->applyRow($row);
					$currencies[] = $instance;
				}
			}
			$consumer($currencies);
		});
	}


	private function applyRow(array $row) : void{
		$this->name = $row["name"];
		$this->symbolBefore = $row["symbolBefore"];
		$this->symbolAfter = $row["symbolAfter"];
	}

	public function getName() : string{
		$this->touchAccess();
		return $this->name;
	}

	public function getSymbolBefore() : string{
		$this->touchAccess();
		return $this->symbolBefore;
	}

	public function getSymbolAfter() : string{
		$this->touchAccess();
		return $this->symbolAfter;
	}

	public function setName(string $newName) : void{
		$this->name = $newName;
		$this->touchAutosave();
	}

	public function setSymbolBefore(string $symbolBefore) : void{
		$this->symbolBefore = $symbolBefore;
		$this->touchAutosave();
	}

	public function setSymbolAfter(string $symbolAfter) : void{
		$this->symbolAfter = $symbolAfter;
		$this->touchAutosave();
	}


	protected function uploadChanges(DataConnector $connector, bool $insert) : void{
		$connector->executeChange(Queries::XIALOTECON_CORE_CURRENCY_UPDATE_HYBRID, [
			"uuid" => $this->getUuid(),
			"name" => $this->name,
			"symbolBefore" => $this->symbolBefore,
			"symbolAfter" => $this->symbolAfter,
		]);
	}

	protected function downloadChanges(DataModelCache $cache) : void{
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_CORE_CURRENCY_LOAD_BY_UUID, ["uuid" => $this->getUuid()], function(SqlSelectResult $result){
			$this->applyRow($result->getRows()[0]);
			$this->onChangesDownloaded();
		});
	}
}
