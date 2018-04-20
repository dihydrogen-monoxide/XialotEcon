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
use DHMO\XialotEcon\Account\AccountTrackedEvent;
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\Init\InitGraph;
use DHMO\XialotEcon\Player\PlayerAccountDefinitionEvent;
use DHMO\XialotEcon\Util\JointPromise;
use DHMO\XialotEcon\Util\StringUtil;
use DHMO\XialotEcon\XialotEcon;
use DHMO\XialotEcon\XialotEconModule;
use pocketmine\event\Listener;
use poggit\libasynql\ConfigException;
use function array_map;
use function array_shift;
use function in_array;
use function is_numeric;

final class BankModule extends XialotEconModule implements Listener{
	protected static function getName() : string{
		return "bank";
	}

	protected static function shouldConstruct(XialotEcon $plugin) : bool{
		return $plugin->getConfig()->getNested("bank.enabled", true);
	}

	public function __construct(XialotEcon $plugin, InitGraph $graph){
		$this->plugin = $plugin;

		if($plugin->getInitMode() === XialotEcon::INIT_INIT){
			$graph->addGenericQuery("bank.init.constant_diff", Queries::XIALOTECON_BANK_INTEREST_INIT_CONSTANT_DIFF, [], "account.init");
			$graph->addGenericQuery("bank.init.constant_ratio", Queries::XIALOTECON_BANK_INTEREST_INIT_CONSTANT_RATIO, [], "account.init");
		}
	}

	public function onStartup() : void{
		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
	}

	/**
	 * @param PlayerAccountDefinitionEvent $event
	 * @ignoreCancelled false
	 */
	public function e_accountDef(PlayerAccountDefinitionEvent $event) : void{
		$config = $event->getConfig();

		if(isset($config["interest"])){
			$event->setPostCreation(function(Account $account) use ($config){
				$interest = explode(" ", $config["interest"]);

				$interestType = "ratio";
				if(!is_numeric($interest[0])){
					$interestType = array_shift($interest);
					if(!in_array($interestType, ["ratio", "diff"], true)){
						throw new ConfigException("Unsupported interest type \"$interestType\"! Supported types: ratio, diff");
					}
				}
				if(!is_numeric($interest[0])){
					throw new ConfigException("Wrong format for interest (\"{$config["interest"]}\")! Correct format: [type = ratio] <amount> [period = 1d]");
				}

				$interestValue = (float) array_shift($interest);

				$interestPeriod = 86400.0;
				if(isset($interest[0])){
					$interestPeriod = StringUtil::parseTime(array_shift($interest), 86400);
				}

				$account->executeAfterNextUpload(function() use ($interestType, $interestPeriod, $interestValue, $account){
					if($interestType === "ratio"){
						ConstantRatioInterest::createNew($this->plugin->getModelCache(), $account, $interestValue, $interestPeriod);
					}elseif($interestType === "diff"){
						ConstantDiffInterest::createNew($this->plugin->getModelCache(), $account, $interestValue, $interestPeriod);
					}
				});
			});
		}
	}

	public function e_modelRetrieved(AccountTrackedEvent $event) : void{
		if($event->isNew()){
			return;
		}

		$event->pause();
		ConstantRatioInterest::forAccount($this->plugin->getModelCache(), $event->getAccount(), function($interests) use ($event){
			JointPromise::build(array_map(function(ConstantRatioInterest $interest){
				return [$interest, "ensureApplyInterest"];
			}, $interests), function() use ($event){
				// only load constant-diff after all constant-ratio have been applied
				ConstantDiffInterest::forAccount($this->plugin->getModelCache(), $event->getAccount(), function($interests) use ($event){
					JointPromise::build(array_map(function(ConstantDiffInterest $interest){
						return [$interest, "ensureApplyInterest"];
					}, $interests), function() use ($event){
						$event->continue();
					});
				});
			});
		});
	}
}
