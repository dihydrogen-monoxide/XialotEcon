<?php

namespace DHMO\xialotecon\provider;

use DHMO\xialotecon\MainClass;
use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\utils\Config;

class JSONProvider extends BaseProvider implements Provider
{

    /** @var Config */
    protected $users;

    public function __construct(MainClass $plugin)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->users = new Config($this->plugin->getDataFolder() . "players.json", Config::JSON, []);
    }

    public function getProvider(): string
    {
        return "json";
    }

    public function getNumberOfAccounts(): int
    {
        return count($this->users->getAll());
    }

    public function createAccount(IPlayer $sender): bool
    {
        $playerName = strtolower($sender->getName());
        $currencyArray = array();
        foreach($this->plugin->getConfig()->get("currencies") as $key => $value){
            array_push($currencyArray, [$key => $value["value"]]);
        }
        $this->users->set($playerName, [
            "name" => $playerName,
            "currencies" => $currencyArray
        ]);
        $this->save();

        return true;
    }

    public function save()
    {
        $this->users->save();
    }

    public function getPlayer(IPlayer $player): array
    {
        $playerName = strtolower($player->getName());
        if($this->users->get($playerName) == false)
        {
            return array();
        }
        else
        {
            return $this->users->get($playerName);
        }
    }

    public function playerHasAccount(IPlayer $player): bool
    {
        if(isset($this->getPlayer($player)["name"]))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getMoney(string $currency = "{{|ALL|}}", IPlayer $player): array
    {
        $ProviderPlayer = $this->getPlayer($player);
        if($currency !== "{{|ALL|}}"){
            if(isset($ProviderPlayer[$currency])){
                return array($ProviderPlayer[$currency]);
            } else {
                return array();
            }
        } else {
            $returnArray = array();
            foreach($this->getPlayer($player)["currencies"] as $key => $value){
                array_push($returnArray, [$key => $value]);
            }
            return $returnArray;
        }
    }
}
