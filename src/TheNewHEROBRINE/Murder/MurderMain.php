<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use TheNewHEROBRINE\Murder\entity\Corpse;
use TheNewHEROBRINE\Murder\entity\projectile\MurderGunProjectile;
use TheNewHEROBRINE\Murder\entity\projectile\MurderKnifeProjectile;

class MurderMain extends PluginBase {

    const MESSAGE_PREFIX = TextFormat::GRAY . "[" . TextFormat::YELLOW . "Murder" . TextFormat::GRAY . "]" . TextFormat::WHITE;

    /** @var Config $config */
    private $config;

    /** @var Config $arenasCfg */
    private $arenasCfg;

    /** @var Config $language */
    private $language;

    /** @var  MurderArena[] $arenas */
    private $arenas = [];

    /** @var MurderListener $listener */
    private $listener;

    /** @var Level $hub */
    private $hub;

    /** @var int $countdown */
    private $countdown;

    public function onEnable() {
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            "language" => "eng",
            "countdown" => 40,
            "maxGameTime" => 1200,
            "hub" => "MurderHub"]
        );
        $this->countdown = $this->getConfig()->get("countdown", 40);
        $this->loadLanguage();
        $this->getServer()->getPluginManager()->registerEvents($this->listener = new MurderListener($this), $this);
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), new MurderCommand($this));
        /** @var string $hub */
        $hub = $this->getConfig()->get("hub", "MurderHub");
        if (!$this->getServer()->isLevelGenerated($hub)){
            $this->getServer()->getLogger()->error($this->translateString("console.hubNotExist", [$hub]));
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        else{
            $this->getServer()->loadLevel($hub);
            $this->hub = $this->getServer()->getLevelByName($hub);
        }
        $this->arenasCfg = new Config($this->getDataFolder() . "arenas.yml");
        foreach ($this->getArenasCfg()->getAll() as $name => $arena) {
            $this->getServer()->loadLevel($name);
            $this->addArena($name, $arena["spawns"], $arena["espawns"]);
        }
        $this->getScheduler()->scheduleRepeatingTask(new MurderTimer($this), 20);
        Entity::registerEntity(MurderKnifeProjectile::class, true);
        Entity::registerEntity(MurderGunProjectile::class, true);
        Entity::registerEntity(Corpse::class, true);
    }

    public function onDisable() {
        foreach ($this->getArenas() as $arena) {
            $arena->stop();
        }
        foreach ($this->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
                if ($entity instanceof MurderGunProjectile or $entity instanceof MurderKnifeProjectile or $entity instanceof Corpse){
                    $entity->flagForDespawn();
                }
            }
        }
    }

    private function loadLanguage(): void {
        $lang = $this->getConfig()->get("language",  "eng");
        $pathToLangs = $this->getFile() . "resources" . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR;
        if (!file_exists($pathToLangs . "$lang.ini")) {
            $this->getLogger()->error("No valid language has been selected. English has been auto selected.");
            $lang = "eng";
        }
        $this->language = new Config($pathToLangs . "$lang.ini", Config::PROPERTIES);
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
     * @param string $str
     * @param array $params
     * @return string
     */
    public function translateString(string $str, array $params = []): string {
        /** @var string $str */
        $str = $this->getLanguage()->get($str);
        foreach($params as $i => $p){
            $str = str_replace("{%$i}", $p, $str);
        }
        return TextFormat::colorize($str);
    }

    /**
     * @param string $text
     * @param Player $recipient
     */
    public function sendMessage(string $text, Player $recipient) {
        $recipient->sendMessage(self::MESSAGE_PREFIX . " " . $text);
    }

    /**
     * @param string $text
     * @param Player[]|null $recipients
     */
    public function broadcastMessage(string $text, $recipients = null) {
        if ($recipients === null){
            $recipients = $this->getServer()->getOnlinePlayers();
        }
        foreach ($recipients as $recipient)
            $this->sendMessage($text, $recipient);
    }

    /**
     * @param string $text
     * @param Player $recipient
     */
    public function sendPopup(string $text, Player $recipient) {
        $recipient->sendPopup($text);
    }

    /**
     * @param string $text
     * @param Player[]|null $recipients
     */
    public function broadcastPopup(string $text, $recipients = null) {
        if ($recipients === null){
            $recipients = $this->getServer()->getOnlinePlayers();
        }
        foreach ($recipients as $recipient)
            $this->sendPopup($text, $recipient);
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
     * @param string $name
     * @return MurderArena|null
     */
    public function getArenaByName(string $name) {
        if (isset($this->getArenas()[$name])){
            return $this->getArenas()[$name];
        }

        return null;
    }

    /**
     * @return Config
     */
    public function getArenasCfg(): Config {
        return $this->arenasCfg;
    }

    /**
     * @return MurderArena[]
     */
    public function getArenas(): array {
        return $this->arenas;
    }

    /**
     * @return int
     */
    public function getCountdown(): int {
        return $this->countdown;
    }

    /**
     * @return Level
     */
    public function getHub(): Level {
        return $this->hub;
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
    public function getLanguage(): Config {
        return $this->language;
    }
}
