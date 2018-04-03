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
use DHMO\XialotEcon\Account\AccountModule;
use DHMO\XialotEcon\Account\AccountPriorityEvent;
use DHMO\XialotEcon\Bank\BankModule;
use DHMO\XialotEcon\Currency\CurrencyModule;
use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use DHMO\XialotEcon\DataModel\DataModelTypeConfig;
use DHMO\XialotEcon\Debug\DebugModule;
use DHMO\XialotEcon\Player\PlayerModule;
use DHMO\XialotEcon\Transaction\TransactionModule;
use DHMO\XialotEcon\Util\JointPromise;
use DHMO\XialotEcon\Util\StringUtil;
use Exception;
use function json_encode;
use const JSON_PRETTY_PRINT;
use LogicException;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use function array_values;
use function mkdir;

final class XialotEcon extends PluginBase{
	private const MODULES = [
		CoreModule::class => [],
		CurrencyModule::class => [
			AccountModule::class => [
				TransactionModule::class => [],
				BankModule::class => [],
			],
		],
		PlayerModule::class => [],
		DebugModule::class => [],
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
		]);

		$this->modelCache = new DataModelCache($this, $connector);
		$this->connector = $connector;

		foreach($this->getConfig()->get("data-model")["types"] as $type => $modelConfig){
			DataModel::$CONFIG[$type] = new DataModelTypeConfig($type, $modelConfig);
		}

		$this->recurseModules(self::MODULES)->then(function(){
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

	private function recurseModules(array $modules) : JointPromise{
		$promise = JointPromise::create();
		/**
		 * @var string|XialotEconModule $name
		 * @var array                   $submodules
		 */
		foreach($modules as $name => $submodules){
			$promise->do($name, function(callable $complete) use ($name, $submodules){
				$module = $name::init($this, function() use ($submodules, $complete){
					$this->recurseModules($submodules)->then($complete);
				});
				if($module !== null){
					$this->modules[$name] = $module;
				}
			});
		}
		return $promise;
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
		$distinctionThreshold = $distinctionThreshold ?? $this->getConfig()->getNested("account.default-distinction", 10);
		$this->getServer()->getPluginManager()->callAsyncEvent($event, function(AccountContributionEvent $event) use ($distinctionThreshold, $consumer){
			if(!libasynql::isPackaged()){
				$this->getLogger()->info("Available accounts found: " . json_encode($event->getAccounts(), JSON_PRETTY_PRINT));
			}
			if(empty($event->getAccounts())){
				$consumer(null);
				return;
			}
			$priorityEvent = new AccountPriorityEvent($event);
			$this->getServer()->getPluginManager()->callAsyncEvent($priorityEvent, function(AccountPriorityEvent $event) use ($distinctionThreshold, $consumer){
				$distinction = 0;
				$result = $event->sortResult($distinction);
				if(!libasynql::isPackaged()){
					$this->getLogger()->info("Sorted result (distinction = $distinction): " . json_encode($result, JSON_PRETTY_PRINT));
				}
				if($distinction >= $distinctionThreshold){
					$consumer(array_values($result)[0]);
				}else{
					throw new LogicException("Time to implement manual account selection!");
					// TODO select with forms
				}
			});
		});
	}


	public function __debugInfo(){
		return [];
	}
}
