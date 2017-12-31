<?php

namespace DHMO\xialotecon;

//Commands
use DHMO\xialotecon\command\Balance;
//Providers
use DHMO\xialotecon\provider\BaseProvider;
use DHMO\xialotecon\provider\MySQLProvider;
use DHMO\xialotecon\provider\YAMLProvider;
use DHMO\xialotecon\provider\JSONProvider;
//PocketMine
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;

class MainClass extends PluginBase implements Listener
{

    /** @var BaseProvider */
    private $provider = null;

    public function onEnable()
    {
        //Make the faction config
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        //$this->getServer()->getPluginManager()->registerEvents(new Events($this), $this);

        switch(strtolower($this->getConfig()->get("provider")))
        {
            case "yaml":
                $this->provider = new YAMLProvider($this);
                break;
            case "json":
                $this->provider = new JSONProvider($this);
                break;
            default:
                $this->getLogger()->error("Invalid database was given. Selecting JSON data provider as default.");
                $this->provider = new JSONProvider($this);
                break;
        }

        $this->getLogger()->notice("Database provider set to " . TextFormat::YELLOW . $this->provider->getProvider());
        if($this->provider->getNumberOfAccounts() == 1)
        {
            $this->getLogger()->notice($this->provider->getNumberOfAccounts() . " account has been loaded.");
        }
        else
        {
            $this->getLogger()->notice($this->provider->getNumberOfAccounts() . " accounts have been loaded.");
        }

        $this->getLogger()->notice("Loaded!");
    }

    public function onDisable()
    {
        $this->getLogger()->info(TextFormat::GREEN . "Unloading!");
    }

    public function onPlayerJoin(PlayerJoinEvent $event): bool
    {
        $player = $event->getPlayer();
        if(!$this->provider->playerHasAccount($player)){
            $this->provider->createAccount($player);
            $this->getLogger()->notice("Created a new account for " . $player->getName());
        }

        return true;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if($command->getName() == "XialotEcon")
        {
            //The subcommand of the command
            $subCmd = strtolower(array_shift($args));
            switch ($subCmd)
            {
                case "bal":
                case "balance":

                    $balance = new Balance($args, $this->provider, $command, $sender);
                    $balance->execute();
                    return true;

                    break;

                default:

                    $sender->sendMessage(TextFormat::RED . "Unknown XialotEcon command. Do '/xe help' for more info.");
                    return true;

            }
            return true;
        }
        return false;
    }
}
