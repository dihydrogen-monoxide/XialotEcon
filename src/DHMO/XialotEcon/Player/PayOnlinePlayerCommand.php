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

use DHMO\XialotEcon\Account;
use DHMO\XialotEcon\Account\AccountContributionEvent;
use DHMO\XialotEcon\JointPromise;
use pocketmine\command\CommandSender;

class PayOnlinePlayerCommand extends PlayerCommand{
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

		JointPromise::create()
			->do("from", function(callable $onComplete) use ($sender, $amount){
				$this->getPlugin()->findAccount(AccountContributionEvent::build()
					->flagBoolean(AccountContributionEvent::FB_SELF_INITIATED, true)
					->flagBoolean(AccountContributionEvent::FB_WITHDRAWAL, true)
					->flagFloat(AccountContributionEvent::FF_AMOUNT, $amount)
					->flagString(AccountContributionEvent::FS_OWNER_TYPE, PlayerModule::OWNER_TYPE_PLAYER)
					->flagString(AccountContributionEvent::FS_OWNER_NAME, $sender->getName())
					, $onComplete);
			})
			->do("to", function(callable $onComplete) use ($amount, $target){
				$this->getPlugin()->findAccount(AccountContributionEvent::build()
					->flagBoolean(AccountContributionEvent::FB_OTHER_INITIATED, true)
					->flagBoolean(AccountContributionEvent::FB_DEPOSIT, true)
					->flagFloat(AccountContributionEvent::FF_AMOUNT, $amount)
					->flagString(AccountContributionEvent::FS_OWNER_TYPE, PlayerModule::OWNER_TYPE_PLAYER)
					->flagString(AccountContributionEvent::FS_OWNER_NAME, $target)
					, $onComplete);
			})
			->then(function(array $result){
				/** @var Account $from */
				$from = $result["from"];
				/** @var Account $to */
				$to = $result["to"];
				// TODO create transaction from $from to $to
			});
	}
}
