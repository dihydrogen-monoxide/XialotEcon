-- #!mysql
-- #{xialotecon.core

-- #{ provider.feed_datum_update
-- #    :uuid string
-- #    :server string
INSERT INTO updates_feed (uuid, fromServer) VALUES (:uuid, :server);
-- #}

-- #{ currency
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
-- #}

-- #{ transaction
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
	source = VALUES(source),
	target = VALUES(target),
	date = VALUES(date),
	sourceReduction = VALUES(sourceReduction),
	targetAddition = VALUES(targetAddition),
	transactionType = VALUES(transactionType)
;
-- #    }
-- #}

-- #}
