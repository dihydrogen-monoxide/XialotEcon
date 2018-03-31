<?php

/**
 * XialotEcon
 *
 * This file is auto-generated from permissions.xml of this project, which is also covered by the license.
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

final class Permissions{
	/**
	 * Default: op
	*/
	public const XIALOTECON = "xialotecon";

	/**
	 * Default: op
	*/
	public const PLAYER = "xialotecon.player";

	/**
	 * Default: op
	*/
	public const PLAYER_CASH = "xialotecon.player.cash";

	/**
	 * Allows depositing money into cash account directly
	 *
	 * Default: true
	*/
	public const PLAYER_CASH_DEPOSIT = "xialotecon.player.cash.deposit";

	/**
	 * Allows spending money from cash account directly
	 *
	 * Default: true
	*/
	public const PLAYER_CASH_WITHDRAW = "xialotecon.player.cash.withdraw";

	/**
	 * Default: op
	*/
	public const PLAYER_BANK = "xialotecon.player.bank";

	/**
	 * Allows depositing money into bank account directly
	 *
	 * Default: true
	*/
	public const PLAYER_BANK_DEPOSIT = "xialotecon.player.bank.deposit";

	/**
	 * Allows spending money from bank account directly
	 *
	 * Default: true
	*/
	public const PLAYER_BANK_WITHDRAW = "xialotecon.player.bank.withdraw";

	/**
	 * Default: op
	*/
	public const PLAYER_TRANSACTION = "xialotecon.player.transaction";

	/**
	 * Allows paying money to another player through the /pay command
	 *
	 * Default: true
	*/
	public const PLAYER_TRANSACTION_PAY = "xialotecon.player.transaction.pay";

	/**
	 * Default: op
	*/
	public const PLAYER_TRANSACTION_BANK = "xialotecon.player.transaction.bank";

	/**
	 * Allows depositing money from cash to bank
	 *
	 * Default: true
	*/
	public const PLAYER_TRANSACTION_BANK_DEPOSIT = "xialotecon.player.transaction.bank.deposit";

	/**
	 * Allows withdrawing money from bank to cash
	 *
	 * Default: true
	*/
	public const PLAYER_TRANSACTION_BANK_WITHDRAWAL = "xialotecon.player.transaction.bank.withdrawal";
}
