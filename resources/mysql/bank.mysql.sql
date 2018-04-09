-- #!mysql
-- #{ xialotecon.bank

-- #{ interest
-- #    { init
-- #        { constant_ratio
CREATE TABLE IF NOT EXISTS bank_interest_constant_ratio (
	interestId  CHAR(34) PRIMARY KEY,
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
	interestId  CHAR(34) PRIMARY KEY,
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
-- #    { insert
-- #        { constant_ratio
-- #            :interestId string
-- #            :accountId string
-- #            :ratio     float
-- #            :period    float
INSERT INTO bank_interest_constant_ratio (interestId, accountId, ratio, period) VALUES (:interestId, :accountId, :ratio, :period);
-- #        }
-- #        { constant_diff
-- #            :interestId string
-- #            :accountId string
-- #            :diff      float
-- #            :period    float
INSERT INTO bank_interest_constant_diff (interestId, accountId, diff, period) VALUES (:interestId, :accountId, :diff, :period);
-- #        }
-- #    }
-- #    { update
-- #        { constant_ratio
-- #            :interestId string
-- #            :ratio float
-- #            :period float
-- #            :lastApplied timestamp
UPDATE bank_interest_constant_ratio
SET ratio = :ratio, period = :period, lastApplied = :lastApplied
WHERE interestId = :interestId;
-- #        }
-- #        { constant_diff
-- #            :interestId string
-- #            :diff float
-- #            :period float
-- #            :lastApplied timestamp
UPDATE bank_interest_constant_diff
SET diff = :diff, period = :period, lastApplied = :lastApplied
WHERE interestId = :interestId;
-- #        }
-- #    }
-- #    { find.by_account
-- #        { constant_ratio
-- #            :accountId string
SELECT
	interestId,
	ratio,
	period,
	lastApplied
FROM bank_interest_constant_ratio
WHERE accountId = :accountId;
-- #        }
-- #        { constant_diff
-- #            :accountId string
SELECT
	interestId,
	diff,
	period,
	lastApplied
FROM bank_interest_constant_diff
WHERE accountId = :accountId;
-- #        }
-- #    }
-- #    { find.by_xoid
-- #        { constant_ratio
-- #            :interestId string
SELECT
	accountId,
	ratio,
	period,
	lastApplied
FROM bank_interest_constant_ratio
WHERE interestId = :interestId;
-- #        }
-- #        { constant_diff
-- #            :interestId string
SELECT
	accountId,
	diff,
	period,
	lastApplied
FROM bank_interest_constant_diff
WHERE interestId = :interestId;
-- #        }
-- #    }
-- #}

-- #}
