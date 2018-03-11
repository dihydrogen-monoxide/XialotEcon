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

use DHMO\XialotEcon\Provider\ProvidedDatum;
use DHMO\XialotEcon\Provider\ProvidedDatumMap;
use pocketmine\utils\UUID;
use function array_map;

class Currency extends ProvidedDatum{
	public const DATUM_TYPE = "xialotecon.core.currency";

	/** @var string */
	private $name;
	/** @var string */
	private $symbolBefore;
	/** @var string */
	private $symbolAfter;

	public static function createNew(ProvidedDatumMap $map, string $name, string $symbolBefore, string $symbolAfter) : Currency{
		$uuid = self::generateUUID(self::DATUM_TYPE);
		$instance = new Currency($map, $uuid, self::DATUM_TYPE, true);
		$instance->name = $name;
		$instance->symbolBefore = $symbolBefore;
		$instance->symbolAfter = $symbolAfter;
		return $instance;
	}

	public static function loadAll(ProvidedDatumMap $map, callable $consumer) : void{
		$map->getProvider()->executeQuery("xialotecon.core.currency.loadAll", [], function($rows) use ($consumer, $map){
			$consumer(array_map(function(array $row) use ($map){
				$instance = new Currency($map, UUID::fromString($row["currencyId"]), self::DATUM_TYPE, false);
				$instance->applyRow($row);
				return $instance;
			}, $rows));
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
		$this->touchLocalModify();
	}

	public function setSymbolBefore(string $symbolBefore) : void{
		$this->symbolBefore = $symbolBefore;
		$this->touchLocalModify();
	}

	public function setSymbolAfter(string $symbolAfter) : void{
		$this->symbolAfter = $symbolAfter;
		$this->touchLocalModify();
	}


	protected function doStore() : void{
		$this->getProvider()->executeQuery("xialotecon.core.currency.update.hybrid", [
			"uuid" => $this->uuid,
			"name" => $this->name,
			"symbolBefore" => $this->symbolBefore,
			"symbolAfter" => $this->symbolAfter,
		]);
	}

	protected function onOutdated() : void{
		$this->getProvider()->executeQuery("xialotecon.core.currency.load.byUuid", ["uuid" => $this->uuid], function($rows){
			$this->applyRow($rows[0]);
			$this->onUpdated();
		});
	}
}
