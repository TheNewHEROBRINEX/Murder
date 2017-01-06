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

    /** @var Player[] $players */
    private $players = [];

    /**
     * MurderArena constructor.
     * @param MurderMain $plugin
     * @param array $spawns
     * @param string $name
     */
    public function __construct(MurderMain $plugin, array $spawns, string $name) {
        $this->plugin = $plugin;
        $this->spawns = $spawns;
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
                //$this->broadcast("La partità inizierà è iniziata!");
                $this->start();
            } elseif ($this->countdown > 10 && $this->countdown % 10 == 0) {
                $this->broadcast("La partità inizierà tra {$this->countdown}");
            } elseif ($this->countdown <= 10) {
                $this->broadcast("La partita inizierà tra {$this->countdown}...");
            }
        }
    }

    public function start() {
        //TODO
    }

    public function quit(Player $player) {
        if (!$this->isRunning())
            array_unshift($this->spawns, $this->players[$player->getName()]);
        unset($this->players[$player->getName()]);
        $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation());
        $this->broadcast(str_replace("{player}", $player->getName(), $this->plugin->getConfig()->get("quit")));
        if ($this->players < 5)
            $this->state = self::GAME_IDLE;
    }

    public function broadcast(string $msg) {
        $this->plugin->getServer()->broadcastMessage($msg, $this->plugin->getServer()->getLevelByName($this->name)->getPlayers());
    }

    public function inArena(Player $player) {
        return isset($this->players[$player->getName()]);
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
