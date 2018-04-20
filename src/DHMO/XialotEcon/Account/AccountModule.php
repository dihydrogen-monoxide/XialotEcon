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

namespace DHMO\XialotEcon\Account;

use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\Init\InitGraph;
use DHMO\XialotEcon\Util\CallbackTask;
use DHMO\XialotEcon\Util\StringUtil;
use DHMO\XialotEcon\XialotEcon;
use DHMO\XialotEcon\XialotEconModule;

final class AccountModule extends XialotEconModule{
	protected static function getName() : string{
		return "account";
	}

	protected static function shouldConstruct(XialotEcon $plugin) : bool{
		return true;
	}

	public function __construct(XialotEcon $plugin, InitGraph $graph){
		$this->plugin = $plugin;
		if($plugin->getInitMode() === XialotEcon::INIT_INIT){
			$graph->addGenericQuery("account.init", Queries::XIALOTECON_ACCOUNT_INIT_TABLE, [], "currency.init");
		}
	}

	public function onStartup() : void{
		$obsoleteTime = StringUtil::parseTime($this->plugin->getConfig()->get("account")["obsolete-time"], 86400.0);
		$this->plugin->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask(function() use ($obsoleteTime){
//			$this->plugin->getConnector()->executeSelect(Queries::XIALOTECON_ACCOUNT_OBSOLETE_FIND, [
//				"time" => $obsoleteTime
//			], function(SqlSelectResult $result) use ($obsoleteTime){
//				$ids = array_map(function(array $row){
//					return $row["accountId"];
//				}, $result->getRows());
//				if(!empty($ids)){
//					$this->plugin->getLogger()->info("Deleting " . count($ids) . " obsolete accounts");
//					$this->plugin->getConnector()->executeChange(Queries::XIALOTECON_ACCOUNT_OBSOLETE_DELETE_LIMITED, [
//						"time" => $obsoleteTime,
//						"limit" => count($ids)
//					]);
//				}
//			});
			$this->plugin->getConnector()->executeChange(Queries::XIALOTECON_ACCOUNT_OBSOLETE_DELETE_UNLIMITED, [
				"time" => $obsoleteTime
			], function(int $changes){
				if($changes > 0){
					$this->plugin->getLogger()->info("Deleted $changes obsolete accounts");
				}
			});
		}), 36000);
	}
}
