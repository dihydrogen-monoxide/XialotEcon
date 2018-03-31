<?php

/*
 * Auto-generated by libasynql-def
 * Created from bank.mysql.sql, core.mysql.sql, player.mysql.sql, bank.sqlite.sql, core.sqlite.sql, player.sqlite.sql
 */

declare(strict_types=1);

namespace DHMO\XialotEcon\Database;

final class Queries{
	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:93
	 */
	public const XIALOTECON_ACCOUNT_INIT_TABLE = "xialotecon.account.init_table";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:113
	 *
	 * <h3>Variables</h3>
	 * - <code>:ownerType</code> string, required in core.mysql.sql
	 * - <code>:ownerName</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_ACCOUNT_LIST_IDS_BY_OWNER = "xialotecon.account.list_ids.by_owner";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:129
	 *
	 * <h3>Variables</h3>
	 * - <code>:ownerType</code> string, required in core.mysql.sql
	 * - <code>:ownerName</code> string, required in core.mysql.sql
	 * - <code>:currency</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_ACCOUNT_LIST_IDS_BY_OWNER_CURRENCY = "xialotecon.account.list_ids.by_owner_currency";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:138
	 *
	 * <h3>Variables</h3>
	 * - <code>:accountTypes</code> string non-empty list, required in core.mysql.sql
	 * - <code>:ownerType</code> string, required in core.mysql.sql
	 * - <code>:ownerName</code> string, required in core.mysql.sql
	 * - <code>:currency</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_ACCOUNT_LIST_IDS_BY_OWNER_CURRENCY_TYPE = "xialotecon.account.list_ids.by_owner_currency_type";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:121
	 *
	 * <h3>Variables</h3>
	 * - <code>:accountTypes</code> string non-empty list, required in core.mysql.sql
	 * - <code>:ownerType</code> string, required in core.mysql.sql
	 * - <code>:ownerName</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_ACCOUNT_LIST_IDS_BY_OWNER_TYPE = "xialotecon.account.list_ids.by_owner_type";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:105
	 *
	 * <h3>Variables</h3>
	 * - <code>:uuid</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_ACCOUNT_LOAD_BY_UUID = "xialotecon.account.load.by_uuid";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:164
	 *
	 * <h3>Variables</h3>
	 * - <code>:uuid</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_ACCOUNT_TOUCH = "xialotecon.account.touch";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:157
	 *
	 * <h3>Variables</h3>
	 * - <code>:accountType</code> string, required in core.mysql.sql
	 * - <code>:ownerType</code> string, required in core.mysql.sql
	 * - <code>:ownerName</code> string, required in core.mysql.sql
	 * - <code>:currency</code> string, required in core.mysql.sql
	 * - <code>:balance</code> float, required in core.mysql.sql
	 * - <code>:uuid</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_ACCOUNT_UPDATE_HYBRID = "xialotecon.account.update.hybrid";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:87
	 *
	 * <h3>Variables</h3>
	 * - <code>:accountId</code> string, required in bank.mysql.sql
	 */
	public const XIALOTECON_BANK_INTEREST_FIND_BY_ACCOUNT_CONSTANT_DIFF = "xialotecon.bank.interest.find.by_account.constant_diff";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:77
	 *
	 * <h3>Variables</h3>
	 * - <code>:accountId</code> string, required in bank.mysql.sql
	 */
	public const XIALOTECON_BANK_INTEREST_FIND_BY_ACCOUNT_CONSTANT_RATIO = "xialotecon.bank.interest.find.by_account.constant_ratio";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:109
	 *
	 * <h3>Variables</h3>
	 * - <code>:interestId</code> string, required in bank.mysql.sql
	 */
	public const XIALOTECON_BANK_INTEREST_FIND_BY_UUID_CONSTANT_DIFF = "xialotecon.bank.interest.find.by_uuid.constant_diff";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:99
	 *
	 * <h3>Variables</h3>
	 * - <code>:interestId</code> string, required in bank.mysql.sql
	 */
	public const XIALOTECON_BANK_INTEREST_FIND_BY_UUID_CONSTANT_RATIO = "xialotecon.bank.interest.find.by_uuid.constant_ratio";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:29
	 */
	public const XIALOTECON_BANK_INTEREST_INIT_CONSTANT_DIFF = "xialotecon.bank.interest.init.constant_diff";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:17
	 */
	public const XIALOTECON_BANK_INTEREST_INIT_CONSTANT_RATIO = "xialotecon.bank.interest.init.constant_ratio";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:45
	 *
	 * <h3>Variables</h3>
	 * - <code>:interestId</code> string, required in bank.mysql.sql
	 * - <code>:accountId</code> string, required in bank.mysql.sql
	 * - <code>:period</code> float, required in bank.mysql.sql
	 * - <code>:diff</code> float, required in bank.mysql.sql
	 */
	public const XIALOTECON_BANK_INTEREST_INSERT_CONSTANT_DIFF = "xialotecon.bank.interest.insert.constant_diff";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:38
	 *
	 * <h3>Variables</h3>
	 * - <code>:interestId</code> string, required in bank.mysql.sql
	 * - <code>:accountId</code> string, required in bank.mysql.sql
	 * - <code>:period</code> float, required in bank.mysql.sql
	 * - <code>:ratio</code> float, required in bank.mysql.sql
	 */
	public const XIALOTECON_BANK_INTEREST_INSERT_CONSTANT_RATIO = "xialotecon.bank.interest.insert.constant_ratio";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:65
	 *
	 * <h3>Variables</h3>
	 * - <code>:lastApplied</code> timestamp, required in bank.mysql.sql
	 * - <code>:interestId</code> string, required in bank.mysql.sql
	 * - <code>:period</code> float, required in bank.mysql.sql
	 * - <code>:diff</code> float, required in bank.mysql.sql
	 */
	public const XIALOTECON_BANK_INTEREST_UPDATE_CONSTANT_DIFF = "xialotecon.bank.interest.update.constant_diff";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/bank.mysql.sql:56
	 *
	 * <h3>Variables</h3>
	 * - <code>:lastApplied</code> timestamp, required in bank.mysql.sql
	 * - <code>:interestId</code> string, required in bank.mysql.sql
	 * - <code>:period</code> float, required in bank.mysql.sql
	 * - <code>:ratio</code> float, required in bank.mysql.sql
	 */
	public const XIALOTECON_BANK_INTEREST_UPDATE_CONSTANT_RATIO = "xialotecon.bank.interest.update.constant_ratio";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:45
	 */
	public const XIALOTECON_CURRENCY_INIT_TABLE = "xialotecon.currency.init_table";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:64
	 *
	 * <h3>Variables</h3>
	 * - <code>:uuid</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_CURRENCY_LOAD_BY_UUID = "xialotecon.currency.load.by_uuid";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:53
	 */
	public const XIALOTECON_CURRENCY_LOAD_ALL = "xialotecon.currency.load_all";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:78
	 *
	 * <h3>Variables</h3>
	 * - <code>:symbolBefore</code> string, required in core.mysql.sql
	 * - <code>:symbolAfter</code> string, required in core.mysql.sql
	 * - <code>:uuid</code> string, required in core.mysql.sql
	 * - <code>:name</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_CURRENCY_UPDATE_HYBRID = "xialotecon.currency.update.hybrid";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:21
	 *
	 * <h3>Variables</h3>
	 * - <code>:server</code> string, required in core.mysql.sql
	 * - <code>:uuid</code> string, required in core.mysql.sql
	 * - <code>:type</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_DATA_MODEL_FEED_UPDATE = "xialotecon.data_model.feed_update";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:25
	 */
	public const XIALOTECON_DATA_MODEL_FETCH_FIRST_UPDATE = "xialotecon.data_model.fetch_first_update";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:34
	 *
	 * <h3>Variables</h3>
	 * - <code>:lastMaxUpdate</code> int, required in core.mysql.sql
	 * - <code>:server</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_DATA_MODEL_FETCH_NEXT_UPDATE = "xialotecon.data_model.fetch_next_update";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:14
	 */
	public const XIALOTECON_DATA_MODEL_INIT_FEED = "xialotecon.data_model.init_feed";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/player.mysql.sql:10
	 */
	public const XIALOTECON_PLAYER_LOGIN_INIT = "xialotecon.player.login.init";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/player.mysql.sql:15
	 *
	 * <h3>Variables</h3>
	 * - <code>:name</code> string, required in player.mysql.sql
	 */
	public const XIALOTECON_PLAYER_LOGIN_TOUCH_INSERT = "xialotecon.player.login.touch.insert";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/player.mysql.sql:21
	 *
	 * <h3>Variables</h3>
	 * - <code>:name</code> string, required in player.mysql.sql
	 */
	public const XIALOTECON_PLAYER_LOGIN_TOUCH_UPDATE = "xialotecon.player.login.touch.update";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/player.mysql.sql:28
	 *
	 * <h3>Variables</h3>
	 * - <code>:name</code> string, required in player.mysql.sql
	 */
	public const XIALOTECON_PLAYER_LOGIN_WHEN = "xialotecon.player.login.when";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/player.mysql.sql:36
	 *
	 * <h3>Variables</h3>
	 * - <code>:accountIds</code> string non-empty list, required in player.mysql.sql
	 */
	public const XIALOTECON_PLAYER_STATS_BALANCE_GROUPED_SUM = "xialotecon.player.stats.balance.grouped_sum";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:179
	 */
	public const XIALOTECON_TRANSACTION_INIT_TABLE = "xialotecon.transaction.init_table";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:192
	 *
	 * <h3>Variables</h3>
	 * - <code>:uuid</code> string, required in core.mysql.sql
	 */
	public const XIALOTECON_TRANSACTION_LOAD_BY_UUID = "xialotecon.transaction.load.by_uuid";

	/**
	 * <h4>Declared in:</h4>
	 * - resources/mysql/core.mysql.sql:211
	 *
	 * <h3>Variables</h3>
	 * - <code>:transactionType</code> string, required in core.mysql.sql
	 * - <code>:sourceReduction</code> float, required in core.mysql.sql
	 * - <code>:targetAddition</code> float, required in core.mysql.sql
	 * - <code>:source</code> string, required in core.mysql.sql
	 * - <code>:target</code> string, required in core.mysql.sql
	 * - <code>:uuid</code> string, required in core.mysql.sql
	 * - <code>:date</code> timestamp, required in core.mysql.sql
	 */
	public const XIALOTECON_TRANSACTION_UPDATE_HYBRID = "xialotecon.transaction.update.hybrid";

}
