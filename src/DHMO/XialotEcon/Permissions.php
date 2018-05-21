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
	 * XialotEcon root permission node
	 *
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
	 * Permission to carry out transactions
	 *
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

	/**
	 * Permission to interact with loans
	 *
	 * Default: op
	*/
	public const PLAYER_LOAN = "xialotecon.player.loan";

	/**
	 * Permission to borrow loans
	 *
	 * Default: op
	*/
	public const PLAYER_LOAN_BORROW = "xialotecon.player.loan.borrow";

	/**
	 * Allows borrowing loans from the server
	 *
	 * Default: true
	*/
	public const PLAYER_LOAN_BORROW_SERVER = "xialotecon.player.loan.borrow.server";

	/**
	 * Allows borrowing loans from other players
	 *
	 * Default: true
	*/
	public const PLAYER_LOAN_BORROW_PLAYER = "xialotecon.player.loan.borrow.player";

	/**
	 * Allows lending loans to other players
	 *
	 * Default: true
	*/
	public const PLAYER_LOAN_LEND = "xialotecon.player.loan.lend";

	/**
	 * Permission for analysing a player's financial state
	 *
	 * Default: op
	*/
	public const PLAYER_ANALYSIS = "xialotecon.player.analysis";

	/**
	 * Permission to check account balance summary by account type
	 *
	 * Default: op
	*/
	public const PLAYER_ANALYSIS_BALANCE = "xialotecon.player.analysis.balance";

	/**
	 * Allows accessing own balance summary report
	 *
	 * Default: true
	*/
	public const PLAYER_ANALYSIS_BALANCE_MY = "xialotecon.player.analysis.balance.my";

	/**
	 * Allows accessing others' balance summary report
	 *
	 * Default: op
	*/
	public const PLAYER_ANALYSIS_BALANCE_HIS = "xialotecon.player.analysis.balance.his";

	/**
	 * Default: op
	*/
	public const ADMIN = "xialotecon.admin";

	/**
	 * Allows accessing the staff portal
	 *
	 * Default: op
	*/
	public const ADMIN_ACCESS = "xialotecon.admin.access";
}
