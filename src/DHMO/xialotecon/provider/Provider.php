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
     * @param IPlayer $sender
     *
     * @return bool
     */
    public function createAccount(IPlayer $player): bool;

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

    /**
     * @param string $currency
     * @param IPlayer $player
     *
     * @return array
     */
    public function getMoney(string $currency, IPlayer $player): array;

    public function save();
}
