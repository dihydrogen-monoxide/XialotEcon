-- Every command line starts with three hyphens and ends with three hyphens
-- Each pair in (DefQuery and {), (EndQuery and }), (UsesVar and var) are equivalent
-- Variables must only follow a space, an open parenthesis or a new line (ignoring leading spaces and tabs)

-- -- { xialotecon.core.provider.feedDatumUpdate #
-- -- var :uuid uuid #
-- -- var :time timestamp #
INSERT INTO updates_feed (uuid) VALUES (:uuid);
-- -- } #

-- -- { xialotecon.core.currency.loadAll #
SELECT
	currencyId,
	name,
	symbolBefore,
	symbolAfter
FROM currencies;
-- -- }

-- -- { xialotecon.core.currency.load.byUuid #
-- -- var :uuid uuid #
SELECT
	currencyId,
	name,
	symbolBefore,
	symbolAfter
FROM currencies
WHERE currencyId = :uuid;
-- -- } #

-- -- { xialotecon.core.currency.update.hybrid #
-- -- var :uuid uuid #
-- -- var :name string #
-- -- var :symbolBefore string #
-- -- var :symbolAfter string #
INSERT INTO currencies
(currencyId, name, symbolBefore, symbolAfter)
VALUES (:uuid, :name, :symbolBefore, :symbolAfter)
ON DUPLICATE KEY UPDATE
	name         = VALUES(name),
	symbolBefore = VALUES(symbolBefore),
	symbolAfter  = VALUES(symbolAfter);
-- -- } #

-- -- { xialotecon.core.account.load.byUuid #
-- -- var :uuid uuid #
SELECT
	accountId,
	ownerType,
	ownerName,
	accountType,
	currency,
	balance
FROM accounts
WHERE accountId = :uuid;
-- -- } #

-- -- { xialotecon.core.account.update.hybrid #
-- -- var :uuid uuid #
-- -- var :ownerType string #
-- -- var :ownerName string #
-- -- var :accountType string #
-- -- var :currency uuid #
-- -- var :balance decimal 5 #
INSERT INTO accounts
(accountId, ownerType, ownerName, accountType, currency, balance)
VALUES (:uuid, :ownerType, :ownerName, :accountType, :currency, :balance)
ON DUPLICATE KEY UPDATE
	ownerType   = VALUES(ownerType),
	ownerName   = VALUES(ownerName),
	accountType = VALUES(accountType),
	currency    = VALUES(currency),
	balance     = VALUES(balance);
-- -- } #
