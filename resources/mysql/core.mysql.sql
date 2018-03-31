-- #!mysql
-- #{xialotecon

-- #{ data_model
-- #    { init_feed
CREATE TABLE IF NOT EXISTS updates_feed (
	updateId   INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	uuid       CHAR(34),
	type       VARCHAR(100),
	time       TIMESTAMP                DEFAULT CURRENT_TIMESTAMP,
	fromServer CHAR(36)
)
	AUTO_INCREMENT = 1;
-- #    }
-- #    { feed_update
-- #        :uuid string
-- #        :type string
-- #        :server string
INSERT INTO updates_feed (uuid, type, fromServer)
VALUES (:uuid, :type, :server);
-- #    }
-- #    { fetch_first_update
SELECT MAX(updateId) maxUpdateId
FROM updates_feed;
-- #    }
-- #    { fetch_next_update
-- #        :lastMaxUpdate int
-- #        :server string
SELECT
	updateId,
	uuid
FROM updates_feed
WHERE updateId > :lastMaxUpdate AND fromServer <> :server;
-- #    }
-- #}

-- #{ currency
-- #    { init_table
CREATE TABLE IF NOT EXISTS currencies (
	currencyId   CHAR(34) PRIMARY KEY,
	name         VARCHAR(40) UNIQUE,
	symbolBefore VARCHAR(40),
	symbolAfter  VARCHAR(40)
);
-- #    }
-- #    { load_all
SELECT
	currencyId,
	name,
	symbolBefore,
	symbolAfter
FROM currencies;
-- #    }

-- #    { load.by_uuid
-- #        :uuid string
SELECT
	currencyId,
	name,
	symbolBefore,
	symbolAfter
FROM currencies
WHERE currencyId = :uuid;
-- #    }

-- #    { update.hybrid
-- #        :uuid string
-- #        :name string
-- #        :symbolBefore string
-- #        :symbolAfter string
INSERT INTO currencies
(currencyId, name, symbolBefore, symbolAfter)
VALUES (:uuid, :name, :symbolBefore, :symbolAfter)
ON DUPLICATE KEY UPDATE
	name         = VALUES(name),
	symbolBefore = VALUES(symbolBefore),
	symbolAfter  = VALUES(symbolAfter);
-- #    }
-- #}

-- #{ account
-- #    { init_table
CREATE TABLE IF NOT EXISTS accounts (
	accountId   CHAR(34) PRIMARY KEY,
	ownerType   VARCHAR(100),
	ownerName   VARCHAR(100),
	accountType VARCHAR(100),
	currency    CHAR(36) REFERENCES currencies (currencyId),
	balance     DECIMAL(35, 5),
	touch       TIMESTAMP,
	KEY (accountType)
);
-- #    }
-- #    { load.by_uuid
-- #        :uuid string
SELECT
	accountId,
	ownerType,
	ownerName,
	accountType,
	currency,
	balance
FROM accounts
WHERE accountId = :uuid;
-- #    }
-- #    { list_ids
-- #        { by_owner
-- #            :ownerType string
-- #            :ownerName string
SELECT accountId, ownerType, ownerName, accountType, currency, balance
FROM accounts
WHERE ownerType = :ownerType AND ownerName = :ownerName;
-- #        }
-- #        { by_owner_type
-- #            :ownerType string
-- #            :ownerName string
-- #            :accountTypes list:string
SELECT accountId, ownerType, ownerName, accountType, currency, balance
FROM accounts
WHERE ownerType = :ownerType AND ownerName = :ownerName AND accountType IN :accountTypes;
-- #        }
-- #        { by_owner_currency
-- #            :ownerType string
-- #            :ownerName string
-- #            :currency string
SELECT accountId, ownerType, ownerName, accountType, currency, balance
FROM accounts
WHERE ownerType = :ownerType AND ownerName = :ownerName ANd currency = :currency;
-- #        }
-- #        { by_owner_currency_type
-- #            :ownerType string
-- #            :ownerName string
-- #            :currency string
-- #            :accountTypes list:string
SELECT accountId, ownerType, ownerName, accountType, currency, balance
FROM accounts
WHERE ownerType = :ownerType AND ownerName = :ownerName AND currency = :currency AND accountType IN :accountTypes;
-- #        }
-- #    }

-- #    { update.hybrid
-- #        :uuid string
-- #        :ownerType string
-- #        :ownerName string
-- #        :accountType string
-- #        :currency string
-- #        :balance float
INSERT INTO accounts
(accountId, ownerType, ownerName, accountType, currency, balance)
VALUES (:uuid, :ownerType, :ownerName, :accountType, :currency, :balance)
ON DUPLICATE KEY UPDATE
	ownerType   = VALUES(ownerType),
	ownerName   = VALUES(ownerName),
	accountType = VALUES(accountType),
	currency    = VALUES(currency),
	balance     = VALUES(balance);
-- #    }

-- #    { touch
-- #        :uuid string
UPDATE accounts
SET touch = CURRENT_TIMESTAMP
WHERE accountId = :uuid;
-- #    }
-- #}

-- #{ transaction
-- #    { init_table
CREATE TABLE IF NOT EXISTS transactions (
	transactionId   CHAR(34) PRIMARY KEY,
	source          CHAR(36) REFERENCES accounts (accountId),
	target          CHAR(36) REFERENCES accounts (accountId),
	date            TIMESTAMP,
	sourceReduction DECIMAL(35, 5),
	targetAddition  DECIMAL(35, 5),
	transactionType VARCHAR(100), -- transaction types with namespaces used for quick filtering. do not store data here; if you need to store transaction-specific data, create a "peer table". this should not be used as an identifier.
	KEY (transactionType)
);
-- #    }
-- #    { load.by_uuid
-- #        :uuid string
SELECT
	transactionId,
	source,
	target,
	sourceReduction,
	targetAddition,
	transactionType,
	date
FROM transactions
WHERE transactionId = :uuid;
-- #    }
-- #    { update.hybrid
-- #        :uuid string
-- #        :source string
-- #        :target string
-- #        :date timestamp
-- #        :sourceReduction float
-- #        :targetAddition float
-- #        :transactionType string
INSERT INTO transactions
(transactionId, source, target, date, sourceReduction, targetAddition, transactionType)
VALUES (:uuid, :source, :target, :date, :sourceReduction, :targetAddition, :transactionType)
ON DUPLICATE KEY UPDATE
	source          = VALUES(source),
	target          = VALUES(target),
	date            = VALUES(date),
	sourceReduction = VALUES(sourceReduction),
	targetAddition  = VALUES(targetAddition),
	transactionType = VALUES(transactionType);
-- #    }
-- #}

-- #}
