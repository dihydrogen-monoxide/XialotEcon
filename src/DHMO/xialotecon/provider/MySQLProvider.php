<?php

namespace DHMO\xialotecon\provider;

use DHMO\xialotecon\MainClass;
use pocketmine\IPlayer;
use pocketmine\Player;

class MySQLProvider extends BaseProvider implements Provider
{

    public function __construct(MainClass $plugin)
    {
        parent::__construct($plugin);
    }

    public function playerIsInFaction(IPlayer $player): bool
    {
        // TODO: Implement playerIsInFaction() method.
    }

    public function createFaction(string $name, Player $player): bool
    {
        // TODO: Implement createFaction() method.
    }

    public function removeFaction(string $name): bool
    {
        // TODO: Implement removeFaction() method.
    }

    public function getFaction(string $name): array
    {
        // TODO: Implement getFaction() method.
    }

    public function getPlayer(IPlayer $player): array
    {
        // TODO: Implement getPlayer() method.
    }

    public function removePlayerFromFaction(IPlayer $player): bool
    {
        // TODO: Implement removePlayerFromFaction() method.
    }

    public function getProvider(): string
    {
        // TODO: Implement getProvider() method.
    }

    public function getNumberOfFactions(): int
    {
        // TODO: Implement getNumberOfFactions() method.
    }

    public function newInvite(IPlayer $to, IPlayer $from): bool
    {
        // TODO: Implement newInvite() method.
    }

    public function hasInvite(IPlayer $player): bool
    {
        // TODO: Implement hasInvite() method.
    }

    public function acceptInvite(IPlayer $player): bool
    {
        // TODO: Implement acceptInvite() method.
    }

    public function save()
    {
        // TODO: Implement save() method.
    }
}
