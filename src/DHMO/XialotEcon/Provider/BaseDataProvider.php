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

use DHMO\XialotEcon\XialotEcon;
use InvalidArgumentException;

abstract class BaseDataProvider implements DataProvider{
	/** @var ProvidedDatumMap */
	protected $map;
	/** @var GenericPreparedStatement[] */
	protected $stmts = [];

	public static function parseFile(XialotEcon $plugin, string $file) : string{
		if($file{0} === "/"){ // absolute path on Unix-like filesystems
			return $file;
		}
		if(strpos($file, ":/") === 1 || strpos($file, ":\\") === 1){ // absolute path on DOS-like filesystems
			return $file;
		}
		return $plugin->getDataFolder() . $file;
	}

	/**
	 * @param GenericPreparedStatement[] $statements
	 */
	public function importStatements(array $statements) : void{
		$this->stmts += $statements; // the first one who takes the namespace wins
	}

	public function executeQuery(string $queryName, array $args = [], ?callable $callback = null) : void{
		if(!isset($this->stmts[$queryName])){
			throw new InvalidArgumentException("Unknown query \"$queryName\"");
		}
		$query = $this->stmts[$queryName]->format($args);
		$this->executeImpl($query, $callback);
	}

	public function init() : void{
		$this->map = new ProvidedDatumMap($this);
	}

	public function cleanup() : void{
		$this->map->clearAll();
	}

	protected abstract function executeImpl(string $query, ?callable $rowsConsumer);
}
