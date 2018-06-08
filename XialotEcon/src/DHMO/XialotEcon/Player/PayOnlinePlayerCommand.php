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
use DHMO\XialotEcon\Transaction\Transaction;
use DHMO\XialotEcon\Util\JointPromise;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

final class PayOnlinePlayerCommand extends PlayerCommand{
	public const TRANSACTION_TYPE = "xialotecon.player.pay.direct";

	public function __construct(){
		parent::__construct("pay", "Pay an amount of money to another player", "/pay <player> <amount>");
	}

	protected function run(CommandSender $sender, array $args) : void{
		parent::run($sender, $args);

		if(!isset($args[1])){
			$this->throwWrongUsage();
		}

		$target = (string) $args[0];
		$amount = (float) $args[1];

		$this->getPlugin()->findAccount(AccountContributionEvent::build()
			->flagBoolean(AccountContributionEvent::FB_SELF_INITIATED, true)
			->flagBoolean(AccountContributionEvent::FB_WITHDRAWAL, true)
			->flagFloat(AccountContributionEvent::FF_AMOUNT, $amount)
			->flagString(AccountContributionEvent::FS_OWNER_TYPE, PlayerModule::OWNER_TYPE_PLAYER)
			->flagString(AccountContributionEvent::FS_OWNER_NAME, $sender->getName()
			), function(?Account $from) use ($sender, $target, $amount){
			if($from === null){
				$sender->sendMessage(TextFormat::RED . "You don't have an account to pay money with");
				return;
			}

			$currency = $from->getCurrency();
			$this->getPlugin()->findAccount(AccountContributionEvent::build()
				->flagBoolean(AccountContributionEvent::FB_OTHER_INITIATED, true)
				->flagBoolean(AccountContributionEvent::FB_DEPOSIT, true)
				->flagFloat(AccountContributionEvent::FF_AMOUNT, $amount)
				->flagString(AccountContributionEvent::FS_OWNER_TYPE, PlayerModule::OWNER_TYPE_PLAYER)
				->flagString(AccountContributionEvent::FS_OWNER_NAME, $target)
				->flagString(AccountContributionEvent::FS_CURRENCY, $currency->getXoid())
				, function(?Account $to) use ($target, $amount, $sender, $from, $currency){
					if($to === null){
						$sender->sendMessage(TextFormat::RED . "$target doesn't have an account to receive money with");
						return;
					}

					Transaction::call($this->getPlugin()->getModelCache(), $from, $amount, $to, $amount, self::TRANSACTION_TYPE, function(Transaction $transaction) use ($sender, $amount, $currency, $target, $to, $from){
						JointPromise::create()
							->do("from.name", function(callable $complete) use ($from){
								$this->getPlugin()->getServer()->getPluginManager()->callAsyncEvent(new AccountDescriptionEvent($from), function(AccountDescriptionEvent $event) use ($complete){
									$complete($event->getDescription());
								});
							})
							->do("to.name", function(callable $complete) use ($to){
								$this->getPlugin()->getServer()->getPluginManager()->callAsyncEvent(new AccountDescriptionEvent($to), function(AccountDescriptionEvent $event) use ($complete){
									$complete($event->getDescription());
								});
							})
							->then(function(array $result) use ($target, $currency, $amount, $sender, $transaction){
								$sender->sendMessage(TextFormat::GREEN . "Sent {$currency->symbolize($amount)} from {$result["from.name"]} to {$result["to.name"]}.");
								$sender->sendMessage(TextFormat::GRAY . "Transaction ID: " . $transaction->getXoid());

								$targetPlayer = $this->getPlugin()->getServer()->getPlayerExact($target);
								if($targetPlayer !== null){
									$targetPlayer->sendMessage("Received {$currency->symbolize($amount)} from {$result["from.name"]} to {$result["to.name"]}.");
									$targetPlayer->sendMessage(TextFormat::GRAY . "Transaction ID: " . $transaction->getXoid());
								}
							});
					}, function(string $message) use ($sender){
						$sender->sendMessage(TextFormat::RED . "Cannot execute transaction! " . $message);
					});
				});
		});
	}
}
