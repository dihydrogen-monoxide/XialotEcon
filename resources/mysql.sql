-- #!mysql
-- #{xialotecon.core.provider.feed_datum_update
-- #    :uuid string
-- #    :server string
INSERT INTO updates_feed (uuid, fromServer) VALUES (:uuid, :server);
-- # }

-- #{xialotecon.core.currency.load_all
SELECT
	currencyId,
	name,
	symbolBefore,
	symbolAfter
FROM currencies;
-- #}

-- #{xialotecon.core.currency.load.by_uuid
-- #    :uuid string
SELECT
	currencyId,
	name,
	symbolBefore,
	symbolAfter
FROM currencies
WHERE currencyId = :uuid;
-- #}

-- #{xialotecon.core.currency.update.hybrid
-- #    :uuid string
-- #    :name string
-- #    :symbolBefore string
-- #    :symbolAfter string
INSERT INTO currencies
(currencyId, name, symbolBefore, symbolAfter)
VALUES (:uuid, :name, :symbolBefore, :symbolAfter)
ON DUPLICATE KEY UPDATE
	name         = VALUES(name),
	symbolBefore = VALUES(symbolBefore),
	symbolAfter  = VALUES(symbolAfter);
-- #}

-- #{xialotecon.core.account.load.by_uuid
-- #    :uuid string
SELECT
	accountId,
	ownerType,
	ownerName,
	accountType,
	currency,
	balance
FROM accounts
WHERE accountId = :uuid;
-- #}

-- #{xialotecon.core.account.update.hybrid
-- #    :uuid string
-- #    :ownerType string
-- #    :ownerName string
-- #    :accountType string
-- #    :currency string
-- #    :balance float
INSERT INTO accounts
(accountId, ownerType, ownerName, accountType, currency, balance)
VALUES (:uuid, :ownerType, :ownerName, :accountType, :currency, :balance)
ON DUPLICATE KEY UPDATE
	ownerType   = VALUES(ownerType),
	ownerName   = VALUES(ownerName),
	accountType = VALUES(accountType),
	currency    = VALUES(currency),
	balance     = VALUES(balance);
-- #}
