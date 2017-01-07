<?php

namespace CastleGames\Murder;

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

    /**
     * MurderArena constructor.
     * @param MurderMain $plugin
     * @param array $spawns
     * @param string $name
     */
    public function __construct(MurderMain $plugin, array $spawns, string $name) {
        $this->plugin = $plugin;
        $this->spawns = shuffle($spawns);
        $this->name = $name;
        $this->countdown = $this->plugin->getConfig()->get("countdown", 90);
        $this->maxTime = $this->plugin->getConfig()->get("maxGameTime", 1200);
    }

    public function join(Player $player) {
        if (!$this->isRunning() && !$this->inArena($player) && count($this->spawns) > 0) {
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
        $playersNames = $players;
        $skin = array();
        foreach ($players as $player){
            $skin[$player] = $this->plugin->getServer()->getPlayer($player)->getSkinData();
        }
        shuffle($skin);
        shuffle($playersNames);
        foreach ($players as $player){
            $player = $this->plugin->getServer()->getPlayer($player);
            $player->setSkin(array_shift($skin), $player->getSkinId());
            $player->setNameTag(array_shift($playersNames));
        }
    }

    /**
     * @param Player $player
     */
    public function quit(Player $player, bool $silent = false) {
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
        return isset($this->players[$player->getName()]);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    public function __toString() {
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
