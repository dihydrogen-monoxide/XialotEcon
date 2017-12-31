-- This is the script used for elaborating the database schema. Do not use this for installing the plugin. This is different from the actual schema.
CREATE TABLE currencies (
	currency INT PRIMARY KEY,
	name TEXT -- what about internationalization?
);

CREATE TABLE accounts (
	accountId INT PRIMARY KEY,
	ownerType TEXT,
	ownerName TEXT,
	accountType TEXT, -- account types with namespaces used for quick filtering, e.g. xialotecon.player.capital, xialotecon.shops.revenue, factions.faction.treasury
	currency INT,
	balance FLOAT,
	inflationPegging FLOAT,
	touch TIMESTAMP,
	KEY(accountType)
);

CREATE TABLE transactions (
	source INT REFERENCES accounts(accountId),
	target INT REFERENCES accounts(accountId),
	date TIMESTAMP,
	amount FLOAT,
	transactionType TEXT, -- transaction types with namespaces used for quick filtering
	active BIT(1),
	KEY(accountType)
);

-- -------------------------------------------------------------------------------------------- --
-- Below are some tables that may or may not be related to this plugin but useful for reference --
-- -------------------------------------------------------------------------------------------- --
-- s2p means server-to-player, p2p means player-to-player
CREATE TABLE s2p_loans (
	accountId INT REFERENCES accounts(accountId), -- loan is stored as a negative-balance account
	compoundFrequency INT, -- in seconds
	compoundRatio FLOAT,
	autoRepay BIT(1)
);

CREATE TABLE block_accounts ( -- accounts held in blocks!
	x INT,
	y INT,
	z INT,
	accountId INT REFERENCES accounts(accountId)
);

CREATE TABLE goods (
	goodsId INT PRIMARY KEY ,
	itemId INT,
	itemDamage INT,
	amountLeft INT
);
CREATE TABLE p2p_shops (
	shopId INT PRIMARY KEY,
	goodsId INT REFERENCES goods(goodsId),
	revenueAccount INT REFERENCES accounts(accountId),
	unitAmount INT,
	price FLOAT,
	currency INT,
	inflationPegging FLOAT
);
CREATE TABLE block_p2p_shops (
	shopId INT REFERENCES p2p_shops(shopId),
	x INT,
	y INT,
	z INT
);
