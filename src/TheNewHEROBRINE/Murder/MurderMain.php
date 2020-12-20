<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

//use JetBrains\PhpStorm\Pure;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use TheNewHEROBRINE\Murder\entity\Corpse;
use TheNewHEROBRINE\Murder\entity\projectile\MurderGunProjectile;
use TheNewHEROBRINE\Murder\entity\projectile\MurderKnifeProjectile;
use TheNewHEROBRINE\Murder\player\MurderPlayer;
use function file_exists;
use function str_replace;
use function strtolower;
use const DIRECTORY_SEPARATOR;

class MurderMain extends PluginBase{
	public const MESSAGE_PREFIX = TextFormat::GRAY . "[" . TextFormat::YELLOW . "Murder" . TextFormat::GRAY . "]" . TextFormat::WHITE;

	private Config $config, $arenasCfg, $language;

	/** @var MurderArena[] */
	private array $arenas = [];

	private MurderListener $listener;
	private int $countdown;

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
		}
		$this->arenasCfg = new Config($this->getDataFolder() . "arenas.yml");
		/** @phpstan-var array{'spawns': list<array{int, int, int}>, 'espawns': list<array{int, int, int}>} $arena */
		foreach($this->getArenasCfg()->getAll() as $name => $arena){
			$name = (string)$name;
			$this->getServer()->loadLevel($name);
			$this->addArena($name, $arena["spawns"], $arena["espawns"]);
		}
		$this->getScheduler()->scheduleRepeatingTask(new MurderTimer($this), 20);
		Entity::registerEntity(MurderKnifeProjectile::class, true);
		Entity::registerEntity(MurderGunProjectile::class, true);
		Entity::registerEntity(Corpse::class, true);
	}

	private function loadLanguage() : void{
		/** @var string $lang */
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
	 * @phpstan-param list<array{int, int, int}> $spawns
	 *
	 * @param int[][] $espawns
	 * @phpstan-param list<array{int, int, int}> $espawns
	 */
	public function addArena(string $name, array $spawns, array $espawns) : MurderArena{
		return $this->arenas[$name] = new MurderArena($this, $name, $spawns, $espawns);
	}

	/**
	 * @param string[] $params
	 */
	public function translateString(string $translationKey, array $params = []) : string{
		/** @var string|false $translation */
		$translation = $this->getLanguage()->get($translationKey);
		if($translation === false){
			return $translationKey;
		}else{
			foreach($params as $i => $p){
				$translation = str_replace("{%$i}", $p, $translation);
			}
			return TextFormat::colorize($translation);
		}
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

	//#[Pure]
	public function getArenaByPlayer(Player $player) : ?MurderArena{
		foreach($this->getArenas() as $arena)
			if($arena->inArena($player)){
				return $arena;
			}

		return null;
	}

	//#[Pure]
	public function getArenaByName(string $name) : ?MurderArena{
		if(isset($this->getArenas()[$name])){
			return $this->getArenas()[$name];
		}

		return null;
	}

	//#[Pure]
	public function findMurderPlayer(Player $player) : ?MurderPlayer{
		foreach($this->getArenas() as $arena){
			if($arena->inArena($player)){
				return $arena->getMurderPlayer($player);
			}
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

	public function getWaitingLobby() : ?Level{
		/** @var string $worldName */
		$worldName = $this->getConfig()->get("hub");
		$this->getServer()->loadLevel($worldName);
		return $this->getServer()->getLevelByName($worldName);
	}

	public function getListener() : MurderListener{
		return $this->listener;
	}

	public function getLanguage() : Config{
		return $this->language;
	}
}
