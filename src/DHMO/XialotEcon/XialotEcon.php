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

use BadMethodCallException;
use DHMO\XialotEcon\Account\AccountContributionEvent;
use DHMO\XialotEcon\Account\AccountModule;
use DHMO\XialotEcon\Account\AccountPriorityEvent;
use DHMO\XialotEcon\Bank\BankModule;
use DHMO\XialotEcon\Currency\CurrencyModule;
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\DataModel\DataModel;
use DHMO\XialotEcon\DataModel\DataModelCache;
use DHMO\XialotEcon\DataModel\DataModelTypeConfig;
use DHMO\XialotEcon\Debug\DebugModule;
use DHMO\XialotEcon\Init\InitGraph;
use DHMO\XialotEcon\Loan\LoanModule;
use DHMO\XialotEcon\Player\PlayerModule;
use DHMO\XialotEcon\Transaction\TransactionModule;
use DHMO\XialotEcon\Util\CallbackTask;
use DHMO\XialotEcon\Util\StringUtil;
use LogicException;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use function array_values;
use function json_encode;
use function microtime;
use const JSON_PRETTY_PRINT;

final class XialotEcon extends PluginBase implements Listener{
	public const CURRENT_DB_VERSION = "1";
	public const INIT_OK = 0;
	public const INIT_INIT = 1;
	public const INIT_ALTER = 2;

	private const MODULES = [
		CoreModule::class,
		CurrencyModule::class,
		AccountModule::class,
		TransactionModule::class,
		BankModule::class,
		LoanModule::class,
		PlayerModule::class,
		DebugModule::class,
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

	private $initMode;
	private $initFrom;

	/** @var XialotEconModule[] */
	private $modules = [];

	/** @var bool */
	private $asyncInitialized = false;

	public function onLoad() : void{
		self::$instance = $this;
		StringUtil::init($this);
	}

	public function onEnable() : void{
		$this->saveDefaultConfig();

		if(!libasynql::isPackaged()){
			$this->getLogger()->warning("This copy of XialotEcon contains an unshaded version of libasynql v3, which means debug mode is enabled. This may cause a significant performance reduction. " . TextFormat::AQUA . "Do not use this copy on a production server. Get a release from Poggit instead: " . TextFormat::UNDERLINE . "https://poggit.pmmp.io/p/XialotEcon");
		}

		$connector = libasynql::create($this, $this->getConfig()->get("database"), [
			"mysql" => [
				"mysql/core.mysql.sql",
				"mysql/player.mysql.sql",
				"mysql/bank.mysql.sql",
				"mysql/loan.mysql.sql",
			],
			"sqlite" => [
				"sqlite/core.sqlite.sql",
				"sqlite/player.sqlite.sql",
				"sqlite/bank.sqlite.sql",
				"sqlite/loan.sqlite.sql",
			],
		]);

		$this->modelCache = new DataModelCache($this, $connector);
		$this->connector = $connector;

		foreach($this->getConfig()->get("data-model")["types"] as $type => $modelConfig){
			DataModel::$CONFIG[$type] = new DataModelTypeConfig($type, $modelConfig);
		}

		$start = microtime(true);
		$this->getLogger()->debug("Starting core.metadata.create");
		$this->getConnector()->executeGeneric(Queries::XIALOTECON_METADATA_CREATE, [], function() use ($start){
			$this->getLogger()->debug("Starting core.metadata.selectVersion");
			$this->getConnector()->executeSelect(Queries::XIALOTECON_METADATA_SELECT_VERSION, [], function(array $rows) use ($start){
				if(empty($rows)){
					$this->initMode = self::INIT_INIT;
					$this->getConnector()->executeChange(Queries::XIALOTECON_METADATA_DECLARE_VERSION, ["version" => self::CURRENT_DB_VERSION]);
				}else{
					$version = $rows[0]["value"];
					if($version === self::CURRENT_DB_VERSION){
						$this->initMode = self::INIT_OK;
					}else{
						$this->initMode = self::INIT_ALTER;
					}
				}

				$graph = new InitGraph();
				/** @var string|XialotEconModule $module */
				foreach(self::MODULES as $module){
					$instance = $module::init($this, $graph);
					if($instance !== null){
						$this->modules[$module] = $instance;
					}
				}
				$graph->execute(function() use ($graph, $start){
					$this->getLogger()->info("Async initialization completed (" . (microtime(true) - $start) * 1000 . " ms)");

					foreach($this->modules as $module){
						$module->onStartup();
					}

					// init players after all listeners have been registered.
					// PocketMine call sequence: call PlayerLoginEvent, add to getOnlinePlayers(), call PlayerJoinEvent
					// Therefore getOnlinePlayers() must have PlayerLoginEvent called, but may not have PlayerJoinEvent called
					foreach($this->getServer()->getOnlinePlayers() as $player){
						foreach($this->modules as $module){
							$module->onPlayerLogin($player);
						}
					}
					foreach($this->getServer()->getOnlinePlayers() as $player){
						if($player->spawned){
							foreach($this->modules as $module){
								$module->onPlayerJoin($player);
							}
						}
					}
					$this->getServer()->getPluginManager()->registerEvents(new PlayerTrafficMonitor($this), $this);

					$this->asyncInitialized = true;

					if(!libasynql::isPackaged()){
						$graph->generateChart();
					}
				});
			});
		});

		$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(function(){
			if(!$this->asyncInitialized){
				$this->getLogger()->critical("XialotEcon failed to initialize in one minute. An error probably occurred. Please check the console logs.");
				$this->getServer()->getPluginManager()->disablePlugin($this);
			}
		}), 1200);
	}

	public function onDisable() : void{
		foreach($this->getServer()->getOnlinePlayers() as $player){
			foreach($this->modules as $module){
				$module->onPlayerQuit($player);
			}
		}
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

	public function getInitMode() : int{
		return $this->initMode;
	}

	public function getInitFrom(){
		if($this->initMode !== self::INIT_ALTER){
			throw new BadMethodCallException("initFrom is only available in INIT_ALTER");
		}
		return $this->initFrom;
	}

	/**
	 * @return XialotEconModule[]
	 */
	public function getModules() : array{
		return $this->modules;
	}

	public function isAsyncInitialized() : bool{
		return $this->asyncInitialized;
	}


	public function __debugInfo(){
		return [];
	}
}
