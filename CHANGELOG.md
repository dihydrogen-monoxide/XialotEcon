XialotEcon Changelog
====================

## Unreleased (Proposed v0.0.1, since 2017-12-31)
- Core
  - Account
    - Orientation defined: a modifier that determines the wealth of the account owner
    - AccountContributionEvent to find potentially appropriate accounts for a transaction
    - AccountPriorityEvent to determine which account should be used
    - AccountDescriptionEvent to display an account to a player
  - Currency
    - Auto-sync among servers
  - Database connection: powered by libasynql
  - Data management: DataModel framework
  - Debug
    - Added /list-cache command
  - Player
  - Transaction
  - Util
    - Config parses time durations in given units
