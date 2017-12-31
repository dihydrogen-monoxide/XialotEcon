<?php

namespace DHMO\xialotecon\command;

use DHMO\xialotecon\provider\BaseProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Balance
{

    public function __construct(array $args, BaseProvider $provider, Command $command, CommandSender $sender)
    {
        $this->args = $args;
        $this->command = $command;
        $this->sender = $sender;
        $this->provider = $provider;
    }

    public function execute()
    {
        if(isset($this->args[0])){
            $money = $this->provider->getMoney("{{|ALL|}}", $this->sender->getServer()->getPlayer($this->args[0]));
            $message = "";
            foreach($money as $key => $value){
                $message .= $key . ": " . $value . "\n";
            }
            $this->sender->sendMessage(TextFormat::GREEN . "Your balance:\n" . TextFormat::AQUA . $message);
        } else {
            if($this->sender instanceof Player){
                $money = $this->provider->getMoney("{{|ALL|}}", $this->sender);
                $message = "";
                foreach($money as $key => $value){
                    $k = key($value[$key]);
                    $message .= $k . ": " . $value[$key][$k] . "\n";
                }
                $this->sender->sendMessage(TextFormat::GREEN . "Your balance:\n" . TextFormat::AQUA . $message);
            } else {
                $this->sender->sendMessage(TextFormat::AQUA . "You must specify a " . TextFormat::BLUE . "player's" . TextFormat::AQUA . " balance to check. Example: /xe bal Player1");
            }
        }
    }
}
