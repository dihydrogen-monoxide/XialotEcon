-- #!mysql
-- #{ xialotecon.player

-- #{ login
-- #    { init
CREATE TABLE IF NOT EXISTS player_login (
	name     VARCHAR(100) PRIMARY KEY,
	joinDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- #    }
-- #    { touch
-- #        { insert
-- #            :name string
INSERT INTO player_login (name, joinDate) VALUES (:name, CURRENT_TIMESTAMP);
-- #        }
-- #        { update
-- #            :name string
UPDATE player_login
SET joinDate = CURRENT_TIMESTAMP
WHERE name = :name;
-- #        }
-- #    }
-- #    { when
-- #        :name string
SELECT joinDate
FROM player_login
WHERE name = :name;
-- #    }
-- #}

-- #}
