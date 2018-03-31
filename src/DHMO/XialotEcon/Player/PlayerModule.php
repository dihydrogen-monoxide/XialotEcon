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

namespace DHMO\XialotEcon\Player;

use DHMO\XialotEcon\Account\Account;
use DHMO\XialotEcon\Account\AccountContributionEvent;
use DHMO\XialotEcon\Account\AccountDescriptionEvent;
use DHMO\XialotEcon\Account\AccountSearchEvent;
use DHMO\XialotEcon\Currency\Currency;
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\Permissions;
use DHMO\XialotEcon\Util\JointPromise;
use DHMO\XialotEcon\XialotEcon;
use DHMO\XialotEcon\XialotEconModule;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use poggit\libasynql\ConfigException;
use poggit\libasynql\result\SqlSelectResult;

final class PlayerModule extends XialotEconModule implements Listener{
	public const OWNER_TYPE_PLAYER = "xialotecon.player.player";
	public const ACCOUNT_TYPE_CASH = "xialotecon.player.cash";
	public const ACCOUNT_TYPE_BANK = "xialotecon.player.bank";

	/** @var PlayerModule */
	private static $instance;

	public static function getInstance() : PlayerModule{
		return self::$instance;
	}

	private $config;

	protected static function getName() : string{
		return "player";
	}

	protected static function shouldConstruct(XialotEcon $plugin) : bool{
		return true;
	}

	protected function __construct(XialotEcon $plugin, callable $onComplete){
		self::$instance = $this;
		$this->plugin = $plugin;
		JointPromise::create()
			->do("login.init", function($complete){
				$this->plugin->getConnector()->executeGeneric(Queries::XIALOTECON_PLAYER_LOGIN_INIT, [], $complete);
			})
			// TODO create other tables
			->then($onComplete);
	}

	public function onStartup() : void{
		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
		$this->plugin->getServer()->getCommandMap()->registerAll("xialotecon", [
			new PayOnlinePlayerCommand(),
			new MyBalanceCommand(),
		]);
		$this->config = $this->plugin->getConfig()->get("player");
	}

	public function onShutdown() : void{
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			$this->onQuit($player);
		}
	}

	public function getConfig() : array{
		return $this->config;
	}

	public function e_join(PlayerJoinEvent $event) : void{
		$this->onJoin($event->getPlayer());
	}

	public function onJoin(Player $player) : void{
		$conn = $this->plugin->getConnector();
		$conn->executeSelect(Queries::XIALOTECON_PLAYER_LOGIN_WHEN, ["name" => $player->getName()], function(SqlSelectResult $result) use ($conn, $player){
			$rows = $result->getRows();
			if(empty($rows)){
				$this->initAccounts($player);
				$conn->executeChange(Queries::XIALOTECON_PLAYER_LOGIN_TOUCH_INSERT, ["name" => $player->getName()]);
			}else{
				// TODO check if account patches are needed
				$conn->executeChange(Queries::XIALOTECON_PLAYER_LOGIN_TOUCH_UPDATE, ["name" => $player->getName()]);
			}
		});
	}

	public function initAccounts(Player $player) : void{
		foreach($this->config["defaults"] as $i => $def){
			if(!isset($def["currency"])){
				throw new ConfigException("Missing \"currency\" in player.defaults.$i");
			}
			$currencyDef = $def["currency"];
			$currency = Currency::getByName($currencyDef);
			if($currency === null){
				throw new ConfigException("Unknown currency \"$currencyDef\" in player.defaults.$i");
			}
			$amount = (float) ($def["amount"] ?? 0);

			$this->plugin->getServer()->getPluginManager()->callEvent($ev = new PlayerAccountDefinitionEvent($player, $def));
			if($ev->getType() === null){
				throw new ConfigException("Unknown account type in player.defaults.$i");
			}

			Account::create($this->plugin->getModelCache(), $ev->getType(), self::OWNER_TYPE_PLAYER, $player->getName(), $currency, $amount, [$ev, "onPostCreation"]);
		}
	}

	public function e_quit(PlayerQuitEvent $event) : void{
		$this->onQuit($event->getPlayer());
	}

	private function onQuit(Player $player) : void{
	}

	/**
	 * @param PlayerAccountDefinitionEvent $event
	 * @priority        LOW
	 * @ignoreCancelled true
	 */
	public function e_accountDef(PlayerAccountDefinitionEvent $event) : void{
		switch($event->getTypeDef()){
			case "cash":
				$event->setType(self::ACCOUNT_TYPE_CASH);
				break;
			case "bank":
				$event->setType(self::ACCOUNT_TYPE_BANK);
				break;
		}
	}

	public function e_accountDesc(AccountDescriptionEvent $event) : void{
		if($event->getAccount()->getAccountType() === self::ACCOUNT_TYPE_CASH){
			$event->setDescription($event->getAccount()->getOwnerName() . "'s cash account");
		}
		if($event->getAccount()->getAccountType() === self::ACCOUNT_TYPE_BANK){
			$event->setDescription($event->getAccount()->getOwnerName() . "'s bank account");
		}
	}

	public function e_accountSearch(AccountSearchEvent $event) : void{
		$event->pause();
		$this->plugin->getConnector()->executeSelect(Queries::XIALOTECON_ACCOUNT_LIST_IDS_BY_OWNER_TYPE, [
			"ownerType" => self::OWNER_TYPE_PLAYER,
			"ownerName" => $event->getPlayer()->getName(),
			"accountTypes" => [self::ACCOUNT_TYPE_CASH, self::ACCOUNT_TYPE_BANK],
		], function(SqlSelectResult $result) use ($event){
			foreach($result->getRows() as $row){
				$event->addAccountId($row["accountId"], 0);
			}
			$event->continue();
		});
	}

	public function e_accountContribute(AccountContributionEvent $event) : void{
		if($event->checkString(AccountContributionEvent::FS_OWNER_TYPE) === self::OWNER_TYPE_PLAYER){
			$player = $this->plugin->getServer()->getPlayerExact($event->checkString(AccountContributionEvent::FS_OWNER_NAME));
			if($player !== null){
				$types = [];
				if($event->checkBoolean(AccountContributionEvent::FB_DEPOSIT)){
					if($player->hasPermission(Permissions::PLAYER_CASH_DEPOSIT)){
						$types[] = self::ACCOUNT_TYPE_CASH;
					}
					if($player->hasPermission(Permissions::PLAYER_BANK_DEPOSIT)){
						$types[] = self::ACCOUNT_TYPE_BANK;
					}
				}
				$event->pause();
				Account::getForOwner($this->plugin->getModelCache(),
					self::OWNER_TYPE_PLAYER,
					$event->checkString(AccountContributionEvent::FS_OWNER_NAME),
					$event->checkString(AccountContributionEvent::FS_CURRENCY, null),
					$types,
					function(array $accounts) use ($event){
						/** @var Account $account */
						foreach($accounts as $account){
							$event->contributeAccount($account);
						}
						$event->continue();
					});
			}
		}
	}
}
