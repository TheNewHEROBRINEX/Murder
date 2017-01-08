<?php

namespace CastleGames\Murder;

use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;

class MurderArena {

    const GAME_IDLE = 0;
    const GAME_STARTING = 1;
    const GAME_RUNNING = 2;

    /** @var MurderMain $plugin */
    private $plugin;

    /** @var array $spawns */
    private $spawns;

    /** @var string $name */
    private $name;

    /** @var int $countdown */
    private $countdown;

    /** @var int $maxTime */
    private $maxTime;

    /** @var int $status */
    private $state = self::GAME_IDLE;

    /** @var array $players */
    private $players = array();

    /** @var array */
    private $skins = array();

    /** @var Player $murder */
    private $murderer;

    /** @var Player[] $bystanders */
    private $bystanders;

    /**
     * MurderArena constructor.
     * @param MurderMain $plugin
     * @param array $spawns
     * @param string $name
     */
    public function __construct(MurderMain $plugin, array $spawns, string $name) {
        $this->plugin = $plugin;
        shuffle($spawns);
        $this->spawns = $spawns;
        $this->name = $name;
        $this->countdown = $this->plugin->getConfig()->get("countdown", 90);
        $this->maxTime = $this->plugin->getConfig()->get("maxGameTime", 1200);
    }

    public function join(Player $player) {
        if (!$this->isRunning() && !$this->inArena($player) && count($this->spawns) > 0) {
            $player->getInventory()->clearAll();
            $player->getInventory()->sendContents($player);
            $spawn = array_shift($this->spawns);
            $this->players[$player->getName()] = $spawn;
            $player->teleport(new Position($spawn[0], $spawn[1], $spawn[2], $this->plugin->getServer()->getLevelByName($this->name)));
            $this->broadcast(str_replace("{player}", $player->getName(), $this->plugin->getConfig()->get("join")));
            if (count($this->players) >= 5 && $this->isIdle())
                $this->state = self::GAME_STARTING;
        }
    }

    public function tick() {
        if ($this->isStarting()) {
            if (--$this->countdown == 0) {
                $this->start();
                $this->broadcast("La partita è iniziata!");
            } elseif ($this->countdown > 10 && $this->countdown % 10 == 0) {
                $this->broadcast("La partita inizierà tra {$this->countdown}");
            } elseif ($this->countdown <= 10) {
                $this->broadcast("La partita inizierà tra {$this->countdown}...");
            }
        }
    }

    public function start() {
        $this->state = self::GAME_RUNNING;
        $players = array_keys($this->players);
        $skins = array();
        foreach ($players as $player) {
            $skins[$player] = $this->plugin->getServer()->getPlayer($player)->getSkinData();
        }
        $this->skins = $skins;
        /*do {
            shuffle($skins);
        } while ($this->skins != $skins);
        do {
            shuffle($players);
        } while (array_keys($this->players) != $players);*/
        foreach (array_keys($this->players) as $player) {
            $player = $this->plugin->getServer()->getPlayer($player);
            $player->setSkin(array_shift($skin), $player->getSkinId());
            $player->setNameTag(array_shift($playersNames));
            $player->respawnToAll();
        }
        $random = array_rand($players, 2);
        $this->murderer = $this->plugin->getServer()->getPlayerExact($players[$random[0]]);
        $this->bystanders[] = $this->plugin->getServer()->getPlayerExact($players[$random[1]]);
        $this->murderer->getInventory()->setItem(0, Item::get(Item::WOODEN_SWORD)->setCustomName("Coltello"));
        $this->murderer->sendMessage("Sei l'assassino!");
        $this->bystanders[0]->getInventory()->setItem(0, Item::get(Item::WOODEN_HOE)->setCustomName("Pistola"));
        $this->bystanders[0]->sendMessage("Sei quello con l'arma!");
    }

    /**
     * @param Player $player
     */
    public function quit(Player $player, bool $silent = false) {
        if ($this->inArena($player)) {
            $player->getInventory()->clearAll();
            $player->getInventory()->sendContents($player);
            if (!$this->isRunning()) {
                array_unshift($this->spawns, $this->players[$player->getName()]);
                shuffle($this->spawns);
            }
            unset($this->players[$player->getName()]);
            $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
            if (!$silent)
                $this->broadcast(str_replace("{player}", $player->getName(), $this->plugin->getConfig()->get("quit")));
            if ($this->players < 5 && $this->isStarting())
                $this->state = self::GAME_IDLE;
        }
    }

    /**
     * @param string $msg
     */
    public function broadcast(string $msg) {
        $this->plugin->getServer()->broadcastMessage($msg, $this->plugin->getServer()->getLevelByName($this->name)->getPlayers());
    }

    /**
     * @return array
     */
    public function getPlayers(): array {
        return $this->players;
    }

    /**
     * @param Player|string $player
     * @return bool
     */
    public function inArena($player) {
        if ($player instanceof Player)
            $player = $player->getName();
        return isset($this->players[$player]);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->getName();
    }

    /**
     * @return int
     */
    public function isIdle(): int {
        return $this->state == 0;
    }

    /**
     * @return int
     */
    public function isStarting(): int {
        return $this->state == 1;
    }

    /**
     * @return int
     */
    public function isRunning(): int {
        return $this->state == 2;
    }
}
