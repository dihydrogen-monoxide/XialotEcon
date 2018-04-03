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

use DHMO\XialotEcon\Account\AccountSearchEvent;
use DHMO\XialotEcon\Database\Queries;
use DHMO\XialotEcon\Permissions;
use DHMO\XialotEcon\UserException;
use DHMO\XialotEcon\Util\JointPromise;
use DHMO\XialotEcon\XialotEconCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use poggit\libasynql\result\SqlSelectResult;
use function assert;
use RuntimeException;
use function ucfirst;

class BalanceCommand extends XialotEconCommand{
	public function __construct(bool $my){
			parent::__construct("balance", "Check your account balance", "/balance", ["bal"]);
			$this->setPermission(Permissions::PLAYER_ANALYSIS_BALANCE_MY . ";" . Permissions::PLAYER_ANALYSIS_BALANCE_HIS);
	}

	protected function run(CommandSender $sender, array $args) : void{
		parent::run($sender, $args);

		if(isset($args[0])){
			$target = $args[0];
		}elseif($sender instanceof Player){
			$target = $sender->getName();
		}else{
			throw new UserException("Usage: /balance <player>");
		}

		$this->getPlugin()->getServer()->getPluginManager()->callAsyncEvent(new AccountSearchEvent($target), function(AccountSearchEvent $event) use ($sender, $target){
			$idLists = $event->getAccountIds();

			$promise = JointPromise::create();
			foreach($idLists as $flags => $ids){
				$promise->do($flags, function(callable $complete) use ($ids){
					$this->getPlugin()->getConnector()->executeSelect(Queries::XIALOTECON_PLAYER_STATS_BALANCE_GROUPED_SUM, [
						"accountIds" => $ids
					], function(SqlSelectResult $result) use ($complete){
						$complete($result->getRows());
					});
				});
			}
			$promise->then(function(array $result) use ($sender, $target){
				$sender->sendMessage("Account summary of {$target}:");

				$grandTotal = [];
				foreach($result as $flags => $rows){
					$line = ucfirst(AccountSearchEvent::flagToString($flags));
					$prefix = ": ";
					foreach($rows as $row){
						$currencyId = $row["currency"];
						$sum = $row["sum"];
						$line .= $prefix . $this->getPlugin()->getModelCache()->getCurrency($currencyId)->symbolize($sum);
						$prefix = ", ";
						$grandTotal[$currencyId] = ($grandTotal[$currencyId] ?? 0) + $sum;
					}
					$sender->sendMessage($line);
				}

				$line = "Total";
				$prefix = ": ";
				foreach($grandTotal as $currencyId => $total){
					$line .= $prefix . $this->getPlugin()->getModelCache()->getCurrency($currencyId)->symbolize($total);
					$prefix = ", ";
				}
				$sender->sendMessage($line);
			});
		});
	}
}
