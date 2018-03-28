-- #!mysql
-- #{ xialotecon.player
-- #    { login
-- #        { init
CREATE TABLE IF NOT EXISTS player_login (
	name VARCHAR(100) PRIMARY KEY,
	joinDate TIMESTAMP
);
-- #        }
-- #    }
-- #}
