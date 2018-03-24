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

use function array_values;
use DHMO\XialotEcon\Account\AccountContributionEvent;
use DHMO\XialotEcon\Account\AccountPriorityEvent;
use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use DHMO\XialotEcon\DataModel\DataModelTypeConfig;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\libasynql;
use function mkdir;

class XialotEcon extends PluginBase{
	/** @var XialotEcon */
	private static $instance;

	public static function getInstance() : XialotEcon{
		return self::$instance;
	}

	public function onLoad() : void{
		self::$instance = $this;
	}

	/** @var DataModelCache */
	private $modelCache;

	public function onEnable() : void{
		//Make the faction config
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();

		$connector = libasynql::create($this, $this->getConfig()->getNested("core.database"), [
			"mysql" => "mysql.sql",
			"sqlite" => "sqlite3.sql",
		]);
		$this->modelCache = new DataModelCache($connector);

		foreach($this->getConfig()->getNested("core.data-model") as $type => $modelConfig){
			DataModel::$CONFIG[$type] = new DataModelTypeConfig($type, $modelConfig);
		}
	}

	public function onDisable() : void{
		if($this->modelCache !== null){
			$this->modelCache->close();
		}
	}

	public function getModelCache() : DataModelCache{
		return $this->modelCache;
	}

	public function findAccount(AccountContributionEvent $event, callable $consumer, int $distinctionThreshold = null){
		$this->getServer()->getPluginManager()->callAsyncEvent($event, function(AccountContributionEvent $event) use($distinctionThreshold, $consumer){
			$priorityEvent = new AccountPriorityEvent($event);
			$this->getServer()->getPluginManager()->callAsyncEvent($priorityEvent, function(AccountPriorityEvent $event) use($distinctionThreshold, $consumer){
				$result = $event->sortResult($distinction);
				if($distinction >= $distinctionThreshold){
					$consumer(array_values($result)[0]);
				}else{
					// TODO select with forms
				}
			});
		});
	}
}
