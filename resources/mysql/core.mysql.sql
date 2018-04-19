-- #!mysql
-- #{xialotecon

-- #{ data_model
-- #    { feed
-- #        { init
CREATE TABLE IF NOT EXISTS updates_feed (
	updateId   INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
	xoid       CHAR(34),
	type       VARCHAR(100),
	time       TIMESTAMP                DEFAULT CURRENT_TIMESTAMP,
	fromServer CHAR(36)
)
	AUTO_INCREMENT = 1;
-- #        }
-- #        { update
-- #            :xoid string
-- #            :type string
-- #            :server string
INSERT INTO updates_feed (xoid, type, fromServer)
VALUES (:xoid, :type, :server);
-- #        }
-- #        { fetch_first
SELECT MAX(updateId) maxUpdateId
FROM updates_feed;
-- #        }
-- #        { fetch_next
-- #            :lastMaxUpdate int
-- #            :server string
SELECT
	updateId,
	xoid
FROM updates_feed
WHERE updateId > :lastMaxUpdate AND fromServer <> :server;
-- #        }
-- #        { clear
-- #            :persistence float
DELETE FROM updates_feed
WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(time) > :persistence;
-- #        }
-- #    }
-- #    { duty
-- #        { init
-- #            { table
CREATE TABLE IF NOT EXISTS duty_lock (
	main       BIT(1) PRIMARY KEY, -- I don't know a better way to make sure the table only has one row.
	serverId   CHAR(36),
	lastActive TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	ON UPDATE CURRENT_TIMESTAMP
)
	ENGINE = 'InnoDB';
-- #            }
-- #            { func
CREATE FUNCTION AcquireDuty(p_serverId CHAR(36))
	RETURNS BOOLEAN
	BEGIN
		DECLARE existsLock BOOLEAN;
		DECLARE inactivity INT;
		DECLARE finalServerId CHAR(36);

		SELECT EXISTS(SELECT serverId
		              FROM duty_lock)
		INTO existsLock;
		IF NOT existsLock THEN
			-- If the database had not been initialized with a duty row, this means no servers had registered for duty before.
			-- Now, a few servers may be competing for acquiring the duty, and we try to INSERT INTO...ON DUPLICATE KEY UPDATE to acquire.
			INSERT INTO duty_lock (main, serverId) VALUES (1, p_serverId)
			ON DUPLICATE KEY UPDATE serverId = p_serverId;
			-- Now, we don't immediately return true, because we don't know if we acquired the lock successfully.
			-- Sleep for 1 second to see if other servers have overwritten our lock.
			DO SLEEP(1);
		ELSE
			-- Let's check if the duty server is dead, i.e. we should take over the duty
			SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(lastActive)
			INTO inactivity
			FROM duty_lock;
			IF inactivity > 15 THEN
				UPDATE duty_lock
				SET serverId = p_serverId;
				-- Also sleep for 1 second to see if other servers have overwritten our lock
				DO SLEEP(1);
			END IF;
		-- else, the duty server is still alive, and we now check if it is us.
		END IF;

		-- eventually, check if we are the one acquiring the duty.
		SELECT serverId
		INTO finalServerId
		FROM duty_lock;

		RETURN (finalServerId = p_serverId);
	END;
-- #            }
-- #        }
-- #        { acquire
-- #            :serverId string
-- #            * Selects <code>[{"result": result}]</code>, where result is a boolean indicating whether we are the duty server.
SELECT AcquireDuty(:serverId) result;
-- #        }
-- #        { maintain
-- #            * Maintains the duty lock. Only run this query if you are sure that you have acquired the duty lock.
-- #            * the serverId parameter is to produce an accurate changed-rows result so that we can check if we have lost the duty lock.
-- #            :serverId string
UPDATE duty_lock
SET lastActive = CURRENT_TIMESTAMP WHERE serverId = :serverId;
-- #        }
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

-- #    { load.by_xoid
-- #        :xoid string
SELECT
	currencyId,
	name,
	symbolBefore,
	symbolAfter
FROM currencies
WHERE currencyId = :xoid;
-- #    }

-- #    { update.hybrid
-- #        :xoid string
-- #        :name string
-- #        :symbolBefore string
-- #        :symbolAfter string
INSERT INTO currencies
(currencyId, name, symbolBefore, symbolAfter)
VALUES (:xoid, :name, :symbolBefore, :symbolAfter)
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
-- #    { load
-- #        {by_xoid
-- #            :xoid string
SELECT
	accountId,
	ownerType,
	ownerName,
	accountType,
	currency,
	balance
FROM accounts
WHERE accountId = :xoid;
-- #        }
-- #        { by_owner
-- #            :ownerType string
-- #            :ownerName string
SELECT
	accountId,
	ownerType,
	ownerName,
	accountType,
	currency,
	balance
FROM accounts
WHERE ownerType = :ownerType AND ownerName = :ownerName;
-- #        }
-- #        { by_owner_type
-- #            :ownerType string
-- #            :ownerName string
-- #            :accountTypes list:string
SELECT
	accountId,
	ownerType,
	ownerName,
	accountType,
	currency,
	balance
FROM accounts
WHERE ownerType = :ownerType AND ownerName = :ownerName AND accountType IN :accountTypes;
-- #        }
-- #        { by_owner_currency
-- #            :ownerType string
-- #            :ownerName string
-- #            :currency string
SELECT
	accountId,
	ownerType,
	ownerName,
	accountType,
	currency,
	balance
FROM accounts
WHERE ownerType = :ownerType AND ownerName = :ownerName ANd currency = :currency;
-- #        }
-- #        { by_owner_currency_type
-- #            :ownerType string
-- #            :ownerName string
-- #            :currency string
-- #            :accountTypes list:string
SELECT
	accountId,
	ownerType,
	ownerName,
	accountType,
	currency,
	balance
FROM accounts
WHERE ownerType = :ownerType AND ownerName = :ownerName AND currency = :currency AND accountType IN :accountTypes;
-- #        }
-- #    }

-- #    { obsolete
-- #        { find
-- #            :time float
SELECT accountId
FROM accounts
WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(touch) >= :time;
-- #        }
-- #        { delete
-- #            { limited
-- #                :time float
-- #                :limit int
DELETE FROM accounts
WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(touch) >= :time
ORDER BY touch ASC
LIMIT :limit;
-- #            }
-- #            { unlimited
-- #                :time float
DELETE FROM accounts
WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(touch) >= :time
ORDER BY touch ASC;
-- #            }
-- #        }
-- #    }

-- #    { update.hybrid
-- #        :xoid string
-- #        :ownerType string
-- #        :ownerName string
-- #        :accountType string
-- #        :currency string
-- #        :balance float
INSERT INTO accounts
(accountId, ownerType, ownerName, accountType, currency, balance)
VALUES (:xoid, :ownerType, :ownerName, :accountType, :currency, :balance)
ON DUPLICATE KEY UPDATE
	ownerType   = VALUES(ownerType),
	ownerName   = VALUES(ownerName),
	accountType = VALUES(accountType),
	currency    = VALUES(currency),
	balance     = VALUES(balance);
-- #    }

-- #    { touch
-- #        :xoid string
UPDATE accounts
SET touch = CURRENT_TIMESTAMP
WHERE accountId = :xoid;
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
-- #    { load.by_xoid
-- #        :xoid string
SELECT
	transactionId,
	source,
	target,
	sourceReduction,
	targetAddition,
	transactionType,
	date
FROM transactions
WHERE transactionId = :xoid;
-- #    }
-- #    { update.hybrid
-- #        :xoid string
-- #        :source string
-- #        :target string
-- #        :date timestamp
-- #        :sourceReduction float
-- #        :targetAddition float
-- #        :transactionType string
INSERT INTO transactions
(transactionId, source, target, date, sourceReduction, targetAddition, transactionType)
VALUES (:xoid, :source, :target, :date, :sourceReduction, :targetAddition, :transactionType)
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
