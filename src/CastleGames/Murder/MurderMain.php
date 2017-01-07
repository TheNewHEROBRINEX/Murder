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
    private $arenas = array();

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this->listener = new MurderListener($this), $this);
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array(
            "join" => "§7[§eMurder§7] §9{player} §fsi è unito alla partita",
            "quit" => "§7[§eMurder§7] §9{player} §fha abbandonato la partita",
            "countdown" => 90,
            "maxGameTime" => 1200
        ));
        $this->arenasCfg = new Config($this->getDataFolder() . "arenas.yml");
        foreach ($this->getArenasCfg()->getAll() as $name => $spawns) {
            $this->addArena($name, $spawns);
            $this->getServer()->loadLevel($name);
        }
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MurderTimer($this), 20);
    }

    /**
     * @param string $name
     * @param array $spawns
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
     * @param string|Player $player
     * @return MurderArena|null
     */
    public function getArenaByPlayer($player): ?MurderArena {
        foreach ($this->getArenas() as $arena)
            if($arena->inArena($player))
                return $arena;
        return null;
    }

    /**
     * @param string $name
     * @return MurderArena|null
     */
    public function getArenaByName(string $name): ?MurderArena {
            return $this->arenas[$name];
    }

    /**
     * @return MurderListener
     */
    public function getListener(): MurderListener {
        return $this->listener;
    }

    /**
     * @return Config
     */
    public function getArenasCfg(): Config {
        return $this->arenasCfg;
    }
}
