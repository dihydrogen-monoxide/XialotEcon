-- This is the MySQL database definition.
-- UUID type: CHAR(36)
-- Money type: DECIMAL(35, 5)
-- Generic namespace type: VARCHAR(100)
CREATE TABLE xialotecon_version (
	version     VARCHAR(16),
	lastUpdated TIMESTAMP
);

CREATE TABLE currencies (
	currencyId   CHAR(36) PRIMARY KEY,
	name         VARCHAR(40), -- the currency name in English
	symbolBefore VARCHAR(40), -- the currency prefix in English
	symbolAfter  VARCHAR(40) -- the currency suffix in English
);

CREATE TABLE accounts (
	accountId        CHAR(36) PRIMARY KEY,
	ownerType        VARCHAR(100), -- example types: "xialotecon.player", "xialotecon.item"
	ownerName        VARCHAR(100), -- the name of the owner under the owner type namespace. only use this for data analysis. do not store data here; if you need to store account-specific data, create a "peer table".
	accountType      VARCHAR(100), -- account types with namespaces used for quick filtering, e.g. xialotecon.player.capital, xialotecon.shops.revenue, factions.faction.treasury. do not store data here; if you need to store account-specific data, create a "peer table". this should not be used as an identifier.
	currency         CHAR(36) REFERENCES currencies (currencyId), -- if someone has multiple currencies, split them to multiple accounts.
	balance          DECIMAL(35, 5), -- the signed amount of capital in this currency that can be attributed to the owner. may be used for data analysis, so this balance should represent actual capital, not other things like shop prices. if this account represents a liability, the balance should be negative.
	touch            TIMESTAMP,
	KEY (accountType)
);

CREATE TABLE transactions (
	transactionId   CHAR(36) PRIMARY KEY,
	source          CHAR(36) REFERENCES accounts (accountId),
	target          CHAR(36) REFERENCES accounts (accountId),
	date            TIMESTAMP,
	sourceReduction DECIMAL(35, 5),
	targetAddition  DECIMAL(35, 5),
	transactionType VARCHAR(100), -- transaction types with namespaces used for quick filtering. do not store data here; if you need to store transaction-specific data, create a "peer table". this should not be used as an identifier.
	initiator       TINYINT(2),
	KEY (transactionType)
);

CREATE TABLE updates_feed (
	updateId INT UNSIGNED PRIMARY KEY AUTO_INCREMENT, -- INT UNSIGNED can sustain 20 updates per second for 6 years and 8 months
	uuid     CHAR(36), -- REFERENCES currencies(currencyId) OR accounts(accountId) OR transactions(transactionId)
	time     TIMESTAMP                DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------------------------------------------- --
-- Below are some tables that may or may not be related to this plugin but useful for reference --
-- -------------------------------------------------------------------------------------------- --
-- s2p means server-to-player, p2p means player-to-player
CREATE TABLE s2p_loans (
	accountId         CHAR(36) REFERENCES accounts (accountId), -- loan is stored as a negative-balance account
	compoundFrequency INT, -- in seconds
	compoundRatio     FLOAT,
	autoRepay         TINYINT(1)
);

CREATE TABLE block_accounts (-- accounts held in blocks!
	x         INT,
	y         INT,
	z         INT,
	accountId INT REFERENCES accounts (accountId)
);

CREATE TABLE goods (
	goodsId    INT PRIMARY KEY,
	itemId     INT,
	itemDamage INT,
	amountLeft INT
);
CREATE TABLE p2p_shops (
	shopId           INT PRIMARY KEY,
	goodsId          INT REFERENCES goods (goodsId),
	revenueAccount   INT REFERENCES accounts (accountId),
	unitAmount       INT,
	price            FLOAT,
	currency         INT,
	inflationPegging FLOAT
);
CREATE TABLE block_p2p_shops (
	shopId INT REFERENCES p2p_shops (shopId),
	x      INT,
	y      INT,
	z      INT
);
CREATE TABLE factions (
	factionId INT PRIMARY KEY,
	-- obvious columns like names etc.
	accountId INT REFERENCES accounts (accountId)
);
