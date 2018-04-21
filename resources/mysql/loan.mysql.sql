-- #!mysql
-- #{ xialotecon.loan

-- #{ init
-- #    { loans
CREATE TABLE IF NOT EXISTS loans (
	loanId           CHAR(34) PRIMARY KEY,
	loanAccount      CHAR(34) NOT NULL,
	creditor         CHAR(34)  DEFAULT NULL,

	visibleName      VARCHAR(100),
	minReturn        DOUBLE,
	normalInterest   DOUBLE,
	normalPeriod     INT,
	principal        DECIMAL(20, 5), -- used for simple interest

	localReturn      DECIMAL(20, 5), -- debt returned within this period. Positive.
	nextInterest     DOUBLE, -- the next interest ratio: first interest the first time, copied from normalInterest afterwards
	nextCompoundDate TIMESTAMP DEFAULT '1970-01-01 08:00:01', -- disable ON UPDATE CURRENT_TIMESTAMP
	FOREIGN KEY (loanAccount) REFERENCES accounts (accountId)
		ON DELETE CASCADE
);
-- #    }
-- #}

-- #{ single
-- #    { insert
-- #        :loanId string
-- #        :accountId string
-- #        :creditor ?string

-- #        :visibleName string
-- #        :minReturn float
-- #        :normalInterest float
-- #        :normalPeriod int
-- #        :principal float

-- #        :localReturn float
-- #        :nextInterest float
-- #        :nextCompoundDate timestamp
INSERT INTO loans (loanId, loanAccount, creditor,
                   visibleName, minReturn, normalInterest, normalPeriod, principal,
                   localReturn, nextInterest, nextCompoundDate)
VALUES
	(:loanId, :accountId, :creditor,
		:visibleName, :minReturn, :normalInterest, :normalPeriod, :principal,
		:localReturn, :nextInterest, :nextCompoundDate);
-- #    }
-- #    { update
-- #        :loanId string
-- #        :accountId string
-- #        :creditor ?string

-- #        :visibleName string
-- #        :minReturn float
-- #        :normalInterest float
-- #        :normalPeriod int
-- #        :principal float

-- #        :localReturn float
-- #        :nextInterest float
-- #        :nextCompoundDate timestamp
UPDATE loans
SET loanAccount      = :accountId,
	creditor         = :creditor,

	visibleName      = :visibleName,
	minReturn        = :minReturn,
	normalInterest   = :normalInterest,
	normalPeriod     = :normalPeriod,
	principal        = :principal,

	localReturn      = :localReturn,
	nextInterest     = :nextInterest,
	nextCompoundDate = :nextCompoundDate
WHERE loanId = :loanId;
-- #    }
-- #    { select
-- #        :loanId string
SELECT
	loanAccount accountId,
	creditor,
	visibleName,
	minReturn,
	normalInterest,
	normalPeriod,
	principal,
	localReturn,
	nextInterest,
	nextCompoundDate
FROM loans
WHERE loanId = :loanId;
-- #    }
-- #}

-- #{ duty
-- #    { compound
-- #        { normal
-- #            * Warning: balance is negative.
UPDATE loans
	INNER JOIN accounts ON loans.loanAccount = accounts.accountId
SET balance          = balance * nextInterest,
	nextInterest     = normalInterest,
	localReturn      = 0,
	nextCompoundDate = FROM_UNIXTIME(UNIX_TIMESTAMP(nextCompoundDate) + normalPeriod)
WHERE localReturn >= (-balance + localReturn) * minReturn AND CURRENT_TIMESTAMP > nextCompoundDate;
-- #        }
-- #        { unpaid
-- #            { server
SELECT
	loanId,
	debtor.balance,
	debtor.ownerType   debtorType,
	debtor.ownerName   debtorName,
	debtor.accountType debtorAccountType,
	localReturn,
	normalInterest,
	nextCompoundDate

FROM loans
	INNER JOIN accounts debtor ON loans.loanAccount = debtor.accountId
WHERE creditor IS NULL AND
      localReturn < (-debtor.balance + localReturn) * minReturn AND
      CURRENT_TIMESTAMP > nextCompoundDate;
-- #            }
-- #            { player
SELECT
	loanId,
	debtor.accountId,
	debtor.balance,
	debtor.ownerType   debtorType,
	debtor.ownerName   debtorName,
	debtor.accountType debtorAccountType,
	creditor.accountId creditor,
	localReturn,
	normalInterest,
	nextCompoundDate

FROM loans
	INNER JOIN accounts debtor ON loans.loanAccount = debtor.accountId
	INNER JOIN accounts creditor ON loans.creditor = creditor.accountId
WHERE creditor IS NOT NULL AND
      localReturn < (-debtor.balance + localReturn) * minReturn AND
      CURRENT_TIMESTAMP > nextCompoundDate;
-- #            }
-- #        }
-- #    }
-- #}

-- #}
