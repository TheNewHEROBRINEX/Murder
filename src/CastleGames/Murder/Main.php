<?php

namespace CastleGames\Murder;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

    private $config, $setspawns;
    
    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, new MurderListener($this));
        $this->config = new Config($this->getDataFolder() . "config.yml");
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                switch (array_shift($args)) {
                    case "join":
                        //TODO
                        break;
                    case "quit":
                        //TODO
                        break;
                    case "setspawns":
                        $world = $sender->getLevel()->getName();
                        $name = $sender->getName();
                        if ($sender->hasPermission("murder.command.setspawns"))
                            if (isset($args[0]) && is_numeric($args[0])) {
                                $this->setspawns[$name][$world] = (int)$args[0];
                                $this->getConfig()->remove($world);
                                $sender->sendMessage("§eSettaggio di§f $args[0] §espawn per il mondo§f {$sender->getLevel()->getName()} §einiziato");
                            }
                        break;
                }
            }
        }
        return true;
    }
}
