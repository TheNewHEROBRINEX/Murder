<?php

namespace CastleGames\Murder;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

    /** @var Config $config */
    private $config;

    /** @var MurderListener $listener */
    private $listener;

    /** @var  MurderArena[] $arenas */
    private $arenas;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this->listener = new MurderListener($this), $this);
        $this->config = new Config($this->getDataFolder() . "config.yml");
        foreach ($this->getConfig()->getAll() as $name => $spawns) {
            $this->addArena($name, $spawns);
        }
    }

    /**
     * @param string $name
     * @param array $spawns
     * @param int $countdown
     * @param int $maxTime
     * @return MurderArena
     */
    public function addArena(string $name, array $spawns, $countdown = 90, $maxTime = 1200) {
        return $this->arenas[$name] = new MurderArena($this, $spawns, $name, $countdown, $maxTime);
    }

    /**
     * @return MurderArena[]
     */
    public function getArenas() {
        return $this->arenas;
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                switch (array_shift($args)) {
                    case "join":
                        if (isset($this->arenas[$args[0]]))
                            $this->arenas[$args[0]]->join($sender);
                        break;
                    case "quit":
                        //TODO
                        break;
                    case "setspawns":
                        $world = $sender->getLevel()->getName();
                        $name = $sender->getName();
                        if ($sender->hasPermission("murder.command.setspawns"))
                            if (isset($args[0]) && is_numeric($args[0])) {
                                $this->listener->setspawns[$name][$world] = (int)$args[0];
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
