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

namespace DHMO\XialotEcon\Currency;

use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use DHMO\XialotEcon\XialotEcon;
use poggit\libasynql\DataConnector;
use function sprintf;

class Currency extends DataModel{
	public const DATUM_TYPE = "xialotecon.currency";

	/** @var Currency[] */
	protected static $byName;

	/** @var string */
	protected $name;
	/** @var string */
	protected $symbolBefore;
	/** @var string */
	protected $symbolAfter;

	public static function createNew(DataModelCache $cache, string $name, string $symbolBefore, string $symbolAfter) : Currency{
		$xoid = self::generateXoid(self::DATUM_TYPE);
		$instance = new Currency($cache, self::DATUM_TYPE, $xoid, true);
		$instance->name = $name;
		$instance->symbolBefore = $symbolBefore;
		$instance->symbolAfter = $symbolAfter;
		return $instance;
	}

	public static function loadAll(DataModelCache $cache, callable $consumer) : void{
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_CURRENCY_LOAD_ALL, [], function(array $rows) use ($consumer, $cache){
			self::$byName = [];
			$currencies = [];
			foreach($rows as $row){
				$xoid = $row["currencyId"];
				if($cache->isTrackingModel($xoid)){
					$currencies[] = $currency = $cache->getCurrency($xoid);
					self::$byName[$currency->getName()] = $currency;
				}else{
					$instance = new Currency($cache, self::DATUM_TYPE, $xoid, false);
					$instance->applyRow($row);
					$currencies[] = $instance;
					self::$byName[$instance->getName()] = $instance;
				}
			}
			$consumer($currencies);
		});
	}

	public static function getByName(string $name) : ?Currency{
		return self::$byName[$name] ?? null;
	}

	public static function fillDefaults(DataModelCache $cache, $config) : void{
		foreach($config as $name => $symbols){
			if(!isset(self::$byName[$name])){
				XialotEcon::getInstance()->getLogger()->info(sprintf('Registering new currency "%s" (e.g. %s)',
					$name, ($symbols["prefix"] ?? "") . "123" . ($symbols["suffix"] ?? "")));
				self::$byName[$name] = self::createNew($cache, $name, $symbols["prefix"] ?? "", $symbols["suffix"] ?? "");
			}elseif(($symbols["prefix"] ?? "") !== self::$byName[$name]->symbolBefore ||
				($symbols["suffix"] ?? "") !== self::$byName[$name]->symbolAfter){
				XialotEcon::getInstance()->getLogger()->warning(sprintf(
					'The registered currency "%s" has different units in the database (%s) and in your config (%s). The value in your config will be used. The value in the database will be overwritten.',
					$name, self::$byName[$name]->symbolize(123), ($symbols["prefix"] ?? "") . "123" . ($symbols["suffix"] ?? "")));
				self::$byName[$name]->onValid(function(Currency $currency) use ($symbols){
					$currency->setSymbolBefore($symbols["prefix"] ?? "");
					$currency->setSymbolAfter($symbols["suffix"] ?? "");
				});
			}
		}
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
		if($this->name !== $newName){
			$this->name = $newName;
			$this->touchAutosave();
		}
	}

	public function setSymbolBefore(string $symbolBefore) : void{
		if($this->symbolBefore !== $symbolBefore){
			$this->symbolBefore = $symbolBefore;
			$this->touchAutosave();
		}
	}

	public function setSymbolAfter(string $symbolAfter) : void{
		if($this->symbolAfter !== $symbolAfter){
			$this->symbolAfter = $symbolAfter;
			$this->touchAutosave();
		}
	}

	public function symbolize(float $amount) : string{
		return $this->symbolBefore . $amount . $this->symbolAfter;
	}


	public function jsonSerialize() : array{
		return parent::jsonSerialize() + [
				"name" => $this->name,
				"symbolBefore" => $this->symbolBefore,
				"symbolAfter" => $this->symbolAfter
			];
	}


	protected function uploadChanges(DataConnector $connector, bool $insert, callable $onComplete) : void{
		$connector->executeChange(Queries::XIALOTECON_CURRENCY_UPDATE_HYBRID, [
			"xoid" => $this->getXoid(),
			"name" => $this->name,
			"symbolBefore" => $this->symbolBefore,
			"symbolAfter" => $this->symbolAfter,
		], $onComplete);
	}

	protected function downloadChanges(DataModelCache $cache) : void{
		$cache->getConnector()->executeSelect(Queries::XIALOTECON_CURRENCY_LOAD_BY_XOID, [
			"xoid" => $this->getXoid()
		], function(array $rows){
			$this->applyRow($rows[0]);
			$this->onChangesDownloaded();
		});
	}
}
