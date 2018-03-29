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

namespace DHMO\XialotEcon\Bank;

use DHMO\XialotEcon\Account\Account;
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\Player\PlayerAccountDefinitionEvent;
use DHMO\XialotEcon\Player\PlayerModule;
use DHMO\XialotEcon\Util\JointPromise;
use DHMO\XialotEcon\Util\StringUtil;
use DHMO\XialotEcon\XialotEcon;
use DHMO\XialotEcon\XialotEconModule;
use Exception;
use pocketmine\event\Listener;
use poggit\libasynql\ConfigException;
use function array_keys;
use function array_shift;
use function implode;
use function is_numeric;

class BankModule extends XialotEconModule implements Listener{
	protected static function getName() : string{
		return "bank";
	}

	protected static function shouldConstruct(XialotEcon $plugin) : bool{
		return true;
	}

	public function __construct(XialotEcon $plugin, callable $onComplete){
		$this->plugin = $plugin;

		JointPromise::create()
			->do("init.constant-diff", function(callable $complete) use ($plugin){
				$plugin->getConnector()->executeGeneric(Queries::XIALOTECON_BANK_INTEREST_INIT_CONSTANT_DIFF, [], $complete);
			})
			->do("init.constant-ratio", function(callable $complete) use ($plugin){
				$plugin->getConnector()->executeGeneric(Queries::XIALOTECON_BANK_INTEREST_INIT_CONSTANT_RATIO, [], $complete);
			})
			->then($onComplete);
	}

	public function onStartup() : void{
		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
	}

	public function e_accountDef(PlayerAccountDefinitionEvent $event):void{
		$config = $event->getConfig();
		if($event->getTypeDef() !== "bank"){
			return;
		}

		$event->setType(PlayerModule::ACCOUNT_TYPE_BANK);

		$event->setPostCreation(function(Account $account) use ($config){
			if(isset($config["interest"])){
				$interest = explode(" ", $config["interest"]);

				$interestQuery = Queries::XIALOTECON_BANK_INTEREST_ADD_CONSTANT_RATIO;
				if(!is_numeric($interest[0])){
					$interestType = array_shift($interest);
					static $interestTypeMapping = [
						"ratio" => Queries::XIALOTECON_BANK_INTEREST_ADD_CONSTANT_RATIO,
						"diff" => Queries::XIALOTECON_BANK_INTEREST_ADD_CONSTANT_DIFF,
					];
					if(!isset($interestTypeMapping[$interestType])){
						throw new ConfigException("Unsupported interest type \"$interestType\"! Supported types: " . implode(", ", array_keys($interestTypeMapping)));
					}
					$interestQuery = $interestTypeMapping[$interestType];
				}
				if(!is_numeric($interest[0])){
					throw new ConfigException("Wrong format for interest (\"{$config["interest"]}\")! Correct format: [type = ratio] <amount> [period = 1d]");
				}

				$interestValue = (float) array_shift($interest);

				$interestPeriod = 86400.0;
				if(isset($interest[0])){
					$interestPeriod = StringUtil::parseTime(array_shift($interest), 86400);
				}

				$account->executeAfterNextUpload(function() use ($interestPeriod, $interestValue, $interestQuery, $account){
					$this->plugin->getConnector()->executeChange($interestQuery, [
						"accountId" => $account->getUuid(),
						"value" => $interestValue,
						"period" => $interestPeriod
					]);
				});
			}
		});
	}
}
