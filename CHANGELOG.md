XialotEcon Changelog
====================

## Unreleased (Proposed v0.0.1, since 2017-12-31)
- Account
  - Orientation defined: a modifier that determines the wealth of the account owner
  - AccountContributionEvent to find potentially appropriate accounts for a transaction
  - AccountPriorityEvent to determine which account should be used
  - AccountDescriptionEvent to display an account to a player
  - Account is touched whenever instance is downloaded, to track obsolete accounts
- Bank
  - Implemented bank interest (constant ratio or constant difference)
- Currency
  - Auto-sync among servers
- Database connection: powered by libasynql
- Data management: DataModel framework
- Debug
  - Added /list-cache command
- Player
  - Auto init accounts
  - /pay command
  - /balance command
  - PlayerAccountDefinitionEvent for adding new user-friendly account types using player.defaults config
- Transaction
- Util
  - Config parses time durations in given units
