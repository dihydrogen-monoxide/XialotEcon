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

namespace DHMO\XialotEcon\Init;

use DHMO\XialotEcon\XialotEcon;
use poggit\libasynql\libasynql;

class MysqlGenericInitNode extends InitNode{
	/** @var string */
	private $queryName;
	/** @var array */
	private $args;

	public function __construct(string $name, string $queryName, array $args, array $parents){
		parent::__construct($name, "trigger_error", $parents); // trigger_error is a placeholder function
		$this->queryName = $queryName;
		$this->args = $args;
	}

	public function execute() : void{
		if(!libasynql::isPackaged()){
			parent::debugExecute();
		}
		XialotEcon::getInstance()->getConnector()->executeGeneric($this->queryName, $this->args, [$this, "onComplete"]);
	}
}
