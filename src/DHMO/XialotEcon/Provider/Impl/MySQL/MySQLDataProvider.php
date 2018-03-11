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

namespace DHMO\XialotEcon\Provider\Impl\MySQL;

use DHMO\XialotEcon\Provider\BaseDataProvider;
use DHMO\XialotEcon\Provider\ProvidedDatumMap;
use DHMO\XialotEcon\XialotEcon;
use RuntimeException;
use const PHP_INT_MAX;
use const PHP_INT_MIN;
use function random_int;
use function usleep;

class MySQLDataProvider extends BaseDataProvider{
	public const CONFIG_NAME = "mysql";

	/** @var XialotEcon */
	private $plugin;
	private $callbacks;
	/** @var MySQLThread */
	private $thread;

	public function __construct(XialotEcon $plugin){
		$this->plugin = $plugin;
	}

	public function init(ProvidedDatumMap $map) : void{
		parent::init($map);
		$this->thread = new MySQLThread_mysqli($this->plugin->getConfig()->getNested("core.mysql"));
		$this->thread->start();
		while(!$this->thread->connCreated()){
			usleep(1000); // wait for connection to be created
		}
		if($this->thread->hasConnError()){
			throw new RuntimeException("MySQL connect error: " . $this->thread->getConnError());
		}
		// TODO: create tables
	}

	public function tick() : void{
		$this->thread->readResults($this->callbacks);
	}

	public function cleanup() : void{
		parent::cleanup();
		$this->thread->stopRunning();
		$this->thread->join();
	}

	protected function executeImpl(string $query, ?callable $rowsConsumer){
		do{
			$queryId = random_int(PHP_INT_MIN, PHP_INT_MAX);
		}while(isset($this->callbacks[$queryId]));
		$this->thread->addQuery($queryId, $query);
		$this->callbacks[$queryId] = $rowsConsumer;
	}

	public function getDialect() : string{
		return "mysql";
	}
}
