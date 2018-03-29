-- #!mysql
-- #{ xialotecon.bank

-- #{ interest
-- #    { init
-- #        { constant_ratio
CREATE TABLE IF NOT EXISTS bank_interest_constant_ratio (
	accountId   CHAR(34),
	ratio       DOUBLE,
	period      DOUBLE,
	lastApplied TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	KEY (accountId),
	FOREIGN KEY (accountId) REFERENCES accounts (accountId)
		ON DELETE CASCADE
);
-- #            }
-- #            { constant_diff
CREATE TABLE IF NOT EXISTS bank_interest_constant_diff (
	accountId   CHAR(34),
	diff        DECIMAL(35, 5),
	period      DOUBLE,
	lastApplied TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	KEY (accountId),
	FOREIGN KEY (accountId) REFERENCES accounts (accountId)
		ON DELETE CASCADE
);
-- #        }
-- #    }
-- #    { add
-- #        { constant_ratio
-- #            :accountId string
-- #            :value     float
-- #            :period    float
INSERT INTO bank_interest_constant_ratio (accountId, ratio, period) VALUES (:accountId, :value, :period);
-- #        }
-- #        { constant_diff
-- #            :accountId string
-- #            :value     float
-- #            :period    float
INSERT INTO bank_interest_constant_diff (accountId, diff, period) VALUES (:accountId, :value, :period);
-- #        }
-- #    }
-- #}

-- #}
