<?php

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use TheNewHEROBRINE\Murder\entities\Corpse;
use TheNewHEROBRINE\Murder\entities\projectiles\MurderGunProjectile;
use TheNewHEROBRINE\Murder\entities\projectiles\MurderKnifeProjectile;

class MurderMain extends PluginBase {

    const MESSAGE_PREFIX = "§7[§eMurder§7]§r§f ";
    /** @var Config $config */
    private $config;

    /** @var Config $arenasCfg */
    private $arenasCfg;

    /** @var MurderListener $listener */
    private $listener;

    /** @var  MurderArena[] $arenas */
    private $arenas = [];

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this->listener = new MurderListener($this), $this);
        $this->getServer()->getCommandMap()->register("murder", new MurderCommand($this));
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            "join" => "§9{player}§f joined the game",
            "quit" => "§9{player}§f left the game",
            "countdown" => 90,
            "maxGameTime" => 1200,
            "hub" => "MurderHub"]
        );
        $hub = $this->getConfig()->get("hub", "MurderHub");
        if (!$this->getServer()->isLevelGenerated($hub)){
            $this->getServer()->getLogger()->error("Il mondo $hub non esiste. Cambia l'hub nelle config");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        else{
            $this->getServer()->loadLevel($hub);
        }
        $this->arenasCfg = new Config($this->getDataFolder() . "arenas.yml");
        foreach ($this->getArenasCfg()->getAll() as $name => $arena) {
            $this->addArena($name, $arena["spawns"], $arena["espawns"]);
            $this->getServer()->loadLevel($name);
        }
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MurderTimer($this), 20);
        Entity::registerEntity(MurderKnifeProjectile::class, true);
        Entity::registerEntity(MurderGunProjectile::class, true);
        Entity::registerEntity(Corpse::class, true);
    }

    /**
     * @return Config
     */
    public function getArenasCfg(): Config {
        return $this->arenasCfg;
    }

    /**
     * @param string $name
     * @param array $spawns
     * @param array $espawns
     * @return MurderArena
     */
    public function addArena(string $name, array $spawns, array $espawns): MurderArena {
        return $this->arenas[$name] = new MurderArena($this, $name, $spawns, $espawns);
    }

    /**
     * @param string $message
     * @param null $recipients
     */
    public function broadcastMessage(string $message, $recipients = null) {
        if ($recipients === null){
            $recipients = $this->getServer()->getOnlinePlayers();
        }
        foreach ($recipients as $recipient)
            $this->sendMessage($message, $recipient);
    }

    /**
     * @param string $message
     * @param Player $recipient
     */
    public function sendMessage(string $message, Player $recipient) {
        $recipient->sendMessage(self::MESSAGE_PREFIX . $message);
    }

    /**
     * @param Player $player
     * @return MurderArena|null
     */
    public function getArenaByPlayer($player) {
        foreach ($this->getArenas() as $arena)
            if ($arena->inArena($player)){
                return $arena;
            }

        return null;
    }

    /**
     * @return MurderArena[]
     */
    public function getArenas(): array {
        return $this->arenas;
    }

    /**
     * @param string $name
     * @return MurderArena|null
     */
    public function getArenaByName(string $name) {
        if (isset($this->arenas[$name])){
            return $this->arenas[$name];
        }

        return null;
    }

    /**
     * @return MurderListener
     */
    public function getListener(): MurderListener {
        return $this->listener;
    }

    public function onDisable() {
        foreach ($this->getServer()->getLevels() as $level)
            foreach ($level->getEntities() as $entity)
                if ($entity instanceof MurderGunProjectile or $entity instanceof MurderKnifeProjectile){
                    $entity->kill();
                }
    }
}
