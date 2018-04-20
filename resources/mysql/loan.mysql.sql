-- #!mysql
-- #{ xialotecon.loan

-- #{ init
-- #    { loans
CREATE TABLE IF NOT EXISTS loans (
	loanId           CHAR(34) PRIMARY KEY,
	loanAccount      CHAR(34) NOT NULL,
	creditor        CHAR(34) DEFAULT NULL,

	visibleName      VARCHAR(100),
	minReturn        DECIMAL(35, 5),
	normalInterest   DOUBLE,
	principal        DECIMAL(35, 5), -- used for simple interest

	localReturn      DECIMAL(35, 5), -- debt returned within this period
	nextInterest     DOUBLE, -- the next interest ratio: first interest the first time, copied from normalInterest afterwards
	nextCompoundDate TIMESTAMP DEFAULT '1970-01-01 08:00:01', -- disable ON UPDATE CURRENT_TIMESTAMP
	FOREIGN KEY (loanAccount) REFERENCES accounts (accountId)
		ON DELETE CASCADE
);
-- #    }
-- #}

-- #{ duty
-- #    { global_compound
UPDATE loans
	INNER JOIN accounts ON loans.loanAccount = accounts.accountId
SET balance      = balance * nextInterest,
	nextInterest = normalInterest,
	localReturn  = 0
WHERE localReturn >= minReturn AND CURRENT_TIMESTAMP > nextCompoundDate;
-- #    }
-- #}

-- #}
