<?php

namespace DHMO\xialotecon;

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
        }

        return true;
    }

    /*public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if($command->getName() == "factionspp")
        {
            //The subcommand of the command
            $subCmd = strtolower(array_shift($args));

            if ($sender instanceof Player)
            {
                switch ($subCmd)
                {
                    case "accept":

                        if($this->provider->playerIsInFaction($sender))
                        {
                            $sender->sendMessage(TextFormat::RED . "You must leave your current faction to join a new one.");
                            return true;
                        }
                        $accept = new Accept($args, $this->provider, $command, $sender);
                        $accept->execute();
                        return true;

                        break;

                    case "kick":

                        if(isset($args[0]))
                        {
                            if(!$this->provider->playerIsInFaction($sender))
                            {
                                $sender->sendMessage(TextFormat::RED . "You must be in a faction to run this command.");
                                return true;
                            }
                            $kick = new Kick($args, $this->provider, $command, $sender);
                            $kick->execute();
                            return true;
                        }
                        else
                        {
                            $sender->sendMessage(TextFormat::RED . "You must specify a player name. Example: /f kick Steve");
                            return true;
                        }

                        break;

                    case "invite":

                        if(isset($args[0]))
                        {
                            if(!$this->provider->playerIsInFaction($sender))
                            {
                                $sender->sendMessage(TextFormat::RED . "You are not a member of any faction.");
                                return true;
                            }
                            $role = $this->provider->getPlayer($sender)["role"];
                            if($role !== Member::MEMBER_LEADER && $role !== Member::MEMBER_OFFICER)
                            {
                                $sender->sendMessage(TextFormat::RED . "Only officers and leaders are allowed to invite new members.");
                                return true;
                            }
                            $invite = new Invite($args, $this->provider, $command, $sender);
                            $invite->execute();
                            return true;
                        }
                        else
                        {
                            $sender->sendMessage(TextFormat::RED . "You must specify a player name. Example: /f invite Steve");
                            return true;
                        }

                        break;

                    case "create":

                        if(isset($args[0]))
                        {
                            if($this->provider->playerIsInFaction($sender))
                            {
                                $sender->sendMessage(TextFormat::RED . "You are already in a faction! You must leave your faction to create a new one.");
                                return true;
                            }
                            else
                            {
                                $create = new CreateFaction($args, $this->provider, $command, $sender);
                                $create->execute();
                                return true;
                            }
                        }
                        else
                        {
                            $sender->sendMessage(TextFormat::RED . "You must specify a faction name. Example: /f create Example");
                            return true;
                        }

                        break;

                    case "delete":
                        if(!$this->provider->playerIsInFaction($sender))
                        {
                            $sender->sendMessage(TextFormat::RED . "You aren't in a faction!");
                            return true;
                        }
                        else
                        {
                            $delete = new DeleteFaction($args, $this->provider, $command, $sender);
                            $delete->execute();
                            return true;
                        }

                        break;

                    case "info":
                        if($this->provider->playerIsInFaction($sender) || isset($args[0]))
                        {
                            $info = new Info($args, $this->provider, $command, $sender);
                            $info->execute();
                            return true;
                        }
                        else
                        {
                            $sender->sendMessage(TextFormat::RED . "You must be in a faction to run this command.");
                            return true;
                        }

                        break;

                    case "motd":
                        if($this->provider->playerIsInFaction($sender))
                        {
                            $motd = new MOTD($args, $this->provider, $command, $sender);
                            $motd->execute();
                            return true;
                        }
                        else
                        {
                            $sender->sendMessage(TextFormat::RED . "You must be in a faction to run this command.");
                            return true;
                        }

                        break;

                    default:
                        $sender->sendMessage(TextFormat::RED . "Unknown FactionsPP command. Do '/f help' for more info.");

                }
                return true;
            }
            else
            {
                $sender->sendMessage(TextFormat::RED . "You must be a player to run FactionsPP commands!");
                return true;
            }
        }
        return false;
    }*/
}
