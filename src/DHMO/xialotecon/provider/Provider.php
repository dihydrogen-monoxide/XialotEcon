<?php

namespace DHMO\xialotecon\provider;

use pocketmine\IPlayer;
use pocketmine\Player;

interface Provider
{

    /**
     * @param IPlayer $player
     *
     * @return bool
     */
    public function playerHasAccount(IPlayer $player): bool;

    /**
     * @param Player $sender
     *
     * @return bool
     */
    public function createAccount(Player $player): bool;

    /**
     * @param IPlayer $player
     *
     * @return array
     */
    public function getPlayer(IPlayer $player): array;

    /**
     * @return string
     */
    public function getProvider(): string;

    /**
     * @return int
     */
    public function getNumberOfAccounts(): int;

    public function save();
}
