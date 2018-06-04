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

use DHMO\XialotEcon\XialotEconEvent;
use function implode;

/**
 * This event is fired when the /balance command is executed.
 *
 * Implementations that introduce new account types should contribute IDs of the accounts they introduced related to the player.
 *
 * If the flags in this event are not inclusive enough, please <a href="https://github.com/dihydrogen-monoxide/XialotEcon/pulls">create a pull request</a>.
 */
class AccountSearchEvent extends XialotEconEvent{
	public const FLAG_SHARED = 2;
	public const FLAG_LOAN = 4;
	public const FLAG_NON_MONETARY = 8;
	public const FLAG_QUICK_ACCESS = 16;

	public static function flagToString(int $flags) : string{
		$adj = [];
		if($flags & self::FLAG_SHARED){
			$adj[] = "shared";
		}
		if($flags & self::FLAG_LOAN){
			$adj[] = "loan";
		}
		if($flags & self::FLAG_NON_MONETARY){
			$adj[] = "non-monetary";
		}
		return empty($adj) ? "personal" : implode(" ", $adj);
	}

	/** @var string */
	private $playerName;

	/** @var string[][] */
	private $accountIds = [];

	public function __construct(string $playerName){
		parent::__construct();
		$this->playerName = $playerName;
	}

	public function getPlayerName() : string{
		return $this->playerName;
	}

	public function addAccountId(string $accountId, int $flags) : void{
		$this->accountIds[$flags][] = $accountId;
	}

	/**
	 * @return string[][]
	 */
	public function getAccountIds() : array{
		return $this->accountIds;
	}
}
