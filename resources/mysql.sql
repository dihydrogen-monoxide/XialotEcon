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
