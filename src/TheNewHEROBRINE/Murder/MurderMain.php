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
use function file_exists;
use function str_replace;
use function strtolower;
use const DIRECTORY_SEPARATOR;

class MurderMain extends PluginBase{

	const MESSAGE_PREFIX = TextFormat::GRAY . "[" . TextFormat::YELLOW . "Murder" . TextFormat::GRAY . "]" . TextFormat::WHITE;

	/** @var Config */
	private $config;

	/** @var Config */
	private $arenasCfg;

	/** @var Config */
	private $language;

	/** @var MurderArena[] */
	private $arenas = [];

	/** @var MurderListener */
	private $listener;

	/** @var Level */
	private $hub;

	/** @var int */
	private $countdown;

	public function onEnable() : void{
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"language" => "eng",
			"countdown" => 40,
			"maxGameTime" => 1200,
			"hub" => "MurderHub"
		]);
		$this->countdown = $this->getConfig()->get("countdown", 40);
		$this->loadLanguage();
		$this->getServer()->getPluginManager()->registerEvents($this->listener = new MurderListener($this), $this);
		$this->getServer()->getCommandMap()->register(strtolower($this->getName()), new MurderCommand($this));
		/** @var string $hub */
		$hub = $this->getConfig()->get("hub", "MurderHub");
		if(!$this->getServer()->isLevelGenerated($hub)){
			$this->getServer()->getLogger()->error($this->translateString("console.hubNotExist", [$hub]));
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}else{
			$this->getServer()->loadLevel($hub);
			$this->hub = $this->getServer()->getLevelByName($hub);
		}
		$this->arenasCfg = new Config($this->getDataFolder() . "arenas.yml");
		foreach($this->getArenasCfg()->getAll() as $name => $arena){
			$this->getServer()->loadLevel($name);
			$this->addArena($name, $arena["spawns"], $arena["espawns"]);
		}
		$this->getScheduler()->scheduleRepeatingTask(new MurderTimer($this), 20);
		Entity::registerEntity(MurderKnifeProjectile::class, true);
		Entity::registerEntity(MurderGunProjectile::class, true);
		Entity::registerEntity(Corpse::class, true);
	}

	public function onDisable() : void{
		foreach($this->getArenas() as $arena){
			$arena->stop();
		}
		foreach($this->getServer()->getLevels() as $level){
			foreach($level->getEntities() as $entity){
				if($entity instanceof MurderGunProjectile or $entity instanceof MurderKnifeProjectile or $entity instanceof Corpse){
					$entity->flagForDespawn();
				}
			}
		}
	}

	private function loadLanguage() : void{
		$lang = $this->getConfig()->get("language", "eng");
		$pathToLangs = $this->getFile() . "resources" . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR;
		if(!file_exists($pathToLangs . "$lang.ini")){
			$this->getLogger()->error("No valid language has been selected. English has been auto selected.");
			$lang = "eng";
		}
		$this->language = new Config($pathToLangs . "$lang.ini", Config::PROPERTIES);
	}

	/**
	 * @param int[][] $spawns
	 * @param int[][] $espawns
	 */
	public function addArena(string $name, array $spawns, array $espawns) : MurderArena{
		return $this->arenas[$name] = new MurderArena($this, $name, $spawns, $espawns);
	}

	/**
	 * @param string[] $params
	 */
	public function translateString(string $str, array $params = []) : string{
		/** @var string $str */
		$str = $this->getLanguage()->get($str);
		foreach($params as $i => $p){
			$str = str_replace("{%$i}", $p, $str);
		}
		return TextFormat::colorize($str);
	}

	public function sendMessage(string $text, Player $recipient) : void{
		$recipient->sendMessage(self::MESSAGE_PREFIX . " " . $text);
	}

	/**
	 * @param Player[]|null $recipients
	 */
	public function broadcastMessage(string $text, ?array $recipients = null) : void{
		if($recipients === null){
			$recipients = $this->getServer()->getOnlinePlayers();
		}
		foreach($recipients as $recipient)
			$this->sendMessage($text, $recipient);
	}

	public function sendPopup(string $text, Player $recipient) : void{
		$recipient->sendPopup($text);
	}

	/**
	 * @param Player[]|null $recipients
	 */
	public function broadcastPopup(string $text, ?array $recipients = null) : void{
		if($recipients === null){
			$recipients = $this->getServer()->getOnlinePlayers();
		}
		foreach($recipients as $recipient)
			$this->sendPopup($text, $recipient);
	}

	public function getArenaByPlayer(Player $player) : ?MurderArena{
		foreach($this->getArenas() as $arena)
			if($arena->inArena($player)){
				return $arena;
			}

		return null;
	}

	public function getArenaByName(string $name) : ?MurderArena{
		if(isset($this->getArenas()[$name])){
			return $this->getArenas()[$name];
		}

		return null;
	}

	public function getArenasCfg() : Config{
		return $this->arenasCfg;
	}

	/**
	 * @return MurderArena[]
	 */
	public function getArenas() : array{
		return $this->arenas;
	}

	public function getCountdown() : int{
		return $this->countdown;
	}

	public function getHub() : Level{
		return $this->hub;
	}

	public function getListener() : MurderListener{
		return $this->listener;
	}

	public function getLanguage() : Config{
		return $this->language;
	}
}
