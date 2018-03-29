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

use DHMO\XialotEcon\Account\AccountContributionEvent;
use DHMO\XialotEcon\Account\AccountPriorityEvent;
use DHMO\XialotEcon\Bank\BankModule;
use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use DHMO\XialotEcon\DataModel\DataModelTypeConfig;
use DHMO\XialotEcon\Debug\DebugModule;
use DHMO\XialotEcon\Player\PlayerModule;
use DHMO\XialotEcon\Util\JointPromise;
use DHMO\XialotEcon\Util\StringUtil;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use function array_values;
use function mkdir;

final class XialotEcon extends PluginBase{
	private const MODULES = [
		CoreModule::class,
		DebugModule::class,
		PlayerModule::class,
		BankModule::class,
	];

	/** @var XialotEcon */
	private static $instance;

	public static function getInstance() : XialotEcon{
		return self::$instance;
	}

	/** @var DataModelCache */
	private $modelCache;
	/** @var DataConnector */
	private $connector;

	/** @var XialotEconModule[] */
	private $modules = [];

	public function onLoad() : void{
		self::$instance = $this;
		StringUtil::init($this);
	}

	public function onEnable() : void{
		//Make the faction config
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();

		$connector = libasynql::create($this, $this->getConfig()->get("database"), [
			"mysql" => [
				"mysql/core.mysql.sql",
				"mysql/player.mysql.sql",
				"mysql/bank.mysql.sql",
			],
			"sqlite" => [
				"sqlite/core.sqlite.sql",
				"sqlite/player.sqlite.sql",
				"sqlite/bank.sqlite.sql",
			],
		], !libasynql::isPackaged());

		$this->modelCache = new DataModelCache($this, $connector);
		$this->connector = $connector;

		foreach($this->getConfig()->get("data-model") as $type => $modelConfig){
			DataModel::$CONFIG[$type] = new DataModelTypeConfig($type, $modelConfig);
		}

		$promise = JointPromise::create();
		/** @var string|XialotEconModule $moduleName */
		foreach(self::MODULES as $moduleName){
			$promise->do($moduleName, function(callable $complete) use ($moduleName){
				$module = $moduleName::init($this, $complete);
				if($module !== null){
					$this->modules[$moduleName] = $module;
				}
			});
		}
		$promise->then(function(){
			$this->getLogger()->info("Async initialization completed");
			foreach($this->modules as $module){
				$module->onStartup();
			}

			// init players after all listeners have been registered.
			// dirty hack. Any better solution?
			foreach($this->getServer()->getOnlinePlayers() as $player){
				$this->getPlayerModule()->onJoin($player);
			}
		});
	}

	public function getPlayerModule() : PlayerModule{
		return $this->modules[PlayerModule::class];
	}

	public function onDisable() : void{
		foreach($this->modules as $module){
			$module->onShutdown();
		}
		if($this->modelCache !== null){
			$this->modelCache->close();
		}
	}

	public function getModelCache() : DataModelCache{
		return $this->modelCache;
	}

	public function getConnector() : DataConnector{
		return $this->connector;
	}

	public function findAccount(AccountContributionEvent $event, callable $consumer, int $distinctionThreshold = null) : void{
		$this->getServer()->getPluginManager()->callAsyncEvent($event, function(AccountContributionEvent $event) use ($distinctionThreshold, $consumer){
			$priorityEvent = new AccountPriorityEvent($event);
			$this->getServer()->getPluginManager()->callAsyncEvent($priorityEvent, function(AccountPriorityEvent $event) use ($distinctionThreshold, $consumer){
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
