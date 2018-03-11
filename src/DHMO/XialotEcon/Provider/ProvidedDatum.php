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

namespace DHMO\XialotEcon\Provider;

use DHMO\XialotEcon\StringUtil;
use InvalidStateException;
use pocketmine\utils\UUID;
use function crc32;
use function hash;
use function microtime;
use function substr;

abstract class ProvidedDatum{
	public static $EXPIRY_CONFIG;

	/** @var ProvidedDatumMap */
	private $datumMap;
	protected $tracked = true;

	/** @var UUID */
	protected $uuid;
	/** @var string */
	private $type;

	/** @var float */
	protected $lastAccess;

	/** @var bool */
	protected $locallyModified;
	/** @var float */
	protected $lastStore;

	/** @var int */
	protected $outdated = 0;

	public static function generateUUID(string $type) : UUID{
		$prefix = hash("crc32b", $type, true);
		$long = hash("sha3-384", microtime(true) . random_bytes(16), true);
		$short = substr($long, 0, 12) ^ substr($long, 12, 12) ^ substr($long, 24, 12) ^ substr($long, 36, 12);
		return UUID::fromBinary($prefix . $short);
	}

	public static function adoptUUID(string $type, UUID $other) : UUID{
		$parts = $other->getParts();
		$part0 = crc32($type); // crc32():int is equivalent to hash("crc32b", raw_output:true):string
		return new UUID($part0, $parts[1], $parts[2], $parts[3]);
	}


	protected function __construct(ProvidedDatumMap $datumMap, UUID $uuid, string $type, bool $new){
		$this->datumMap = $datumMap;
		$this->uuid = $uuid;
		$this->type = $type;
		$this->lastAccess = microtime(true);
		$this->lastStore = microtime(true);
		$this->locallyModified = $new;
		$datumMap->onDatumLoaded($this);
	}

	public function getDatumMap() : ProvidedDatumMap{
		return $this->datumMap;
	}

	public function isTracked() : bool{
		return $this->tracked;
	}

	public function getUUID() : UUID{
		return $this->uuid;
	}

	public function getType() : string{
		return $this->type;
	}

	public function getProvider() : DataProvider{
		return $this->datumMap->getProvider();
	}


	public function shouldGarbage() : bool{
		$garbagePolicy = ProvidedDatum::$EXPIRY_CONFIG[$this->type]["garbage"];
		$timeout = StringUtil::parseTime($garbagePolicy);
		return microtime(true) > $this->lastAccess + $timeout;
	}

	public function setGarbage() : void{
		$this->tracked = false;
	}

	protected function touchAccess() : void{
		if(!$this->tracked){
			throw new InvalidStateException("Cannot access a garbage ProvidedDatum");
		}
		$this->lastAccess = microtime(true);
	}


	public function isLocallyModified() : bool{
		return $this->locallyModified;
	}

	protected function touchLocalModify() : void{
		if(!$this->tracked){
			throw new InvalidStateException("Cannot modify a garbage ProvidedDatum");
		}
		if($this->outdated > 0){
			throw new InvalidStateException("Cannot modify a ProvidedDatum when it is being updated.");
		}
		$this->locallyModified = true;
	}

	public function shouldStore() : bool{
		if(!$this->locallyModified){
			return false;
		}
		$storePolicy = ProvidedDatum::$EXPIRY_CONFIG[$this->type]["store"];
		$timeout = StringUtil::parseTime($storePolicy);
		return microtime(true) > $this->lastStore + $timeout;
	}

	public final function store() : void{
		$this->doStore();
		$this->datumMap->getProvider()->executeQuery("xialotecon.core.provider.feedDatumUpdate", ["uuid" => $this->uuid]);
		$this->lastStore = microtime(true);
	}

	protected abstract function doStore() : void;


	public function isOutdated() : bool{
		return $this->outdated > 0;
	}

	public function setOutdated() : void{
		++$this->outdated;
		$this->onOutdated();
	}

	protected function onUpdated() : void{
		--$this->outdated;
	}

	protected abstract function onOutdated() : void;
}
