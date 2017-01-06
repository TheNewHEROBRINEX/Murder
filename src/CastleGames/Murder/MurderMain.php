<?php

namespace CastleGames\Murder;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class MurderMain extends PluginBase {

    /** @var Config $config */
    private $config;

    /** @var Config $arenasCfg */
    private $arenasCfg;

    /** @var MurderListener $listener */
    private $listener;

    /** @var  MurderArena[] $arenas */
    private $arenas;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this->listener = new MurderListener($this), $this);
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array(
            "quit" => "§7[§eMurder§7] §9{player} §fsi è unito alla partita",
            "join" => "§7[§eMurder§7] §9{player} §fha abbandonato la patita",
            "countdown" => 90,
            "maxGameTime" => 1200
        ));
        $this->arenasCfg = new Config($this->getDataFolder() . "arenas.yml");
        $countdown = $this->getConfig()->get("countdown", 90);
        $maxTime = $this->getConfig()->get("maxGameTime", 1200);
        foreach ($this->getArenasCfg()->getAll() as $name => $spawns) {
            $this->addArena($name, $spawns, $countdown, $maxTime);
        }
    }

    /**
     * @param string $name
     * @param array $spawns
     * @param int $countdown
     * @param int $maxTime
     * @return MurderArena
     */
    public function addArena(string $name, array $spawns): MurderArena {
        return $this->arenas[$name] = new MurderArena($this, $spawns, $name);
    }

    /**
     * @return MurderArena[]
     */
    public function getArenas(): array {
        return $this->arenas;
    }

    /**
     * @return Config
     */
    public function getArenasCfg(): Config {
        return $this->arenasCfg;
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool {
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
                                $this->getArenasCfg()->remove($world);
                                $sender->sendMessage("§eSettaggio di§f $args[0] §espawn per il mondo§f {$sender->getLevel()->getName()} §einiziato");
                            }
                        break;
                }
            }
        }
        return true;
    }
}
