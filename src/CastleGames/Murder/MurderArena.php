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
    private $status = self::GAME_IDLE;

    /** @var Player[] $players */
    private $players = [];

    /**
     * MurderArena constructor.
     * @param MurderMain $plugin
     * @param array $spawns
     * @param string $name
     * @param int $countdown
     * @param int $maxTime
     */
    public function __construct(MurderMain $plugin, array $spawns, string $name, int $countdown, int $maxTime) {
        $this->plugin = $plugin;
        $this->spawns = $spawns;
        $this->name = $name;
        $this->countdown = $countdown;
        $this->maxTime = $maxTime;
    }

    public function join(Player $player) {
        $spawn = array_shift($this->spawns);
        $this->players[$player->getName()] = $spawn;
        $player->teleport(new Position($spawn[0], $spawn[1], $spawn[2], $this->plugin->getServer()->getLevelByName($this->name)));
    }

    /**
     * @return int
     */
    public function isIdle(): int {
        return $this->status == 0;
    }

    /**
     * @return int
     */
    public function isStarting(): int {
        return $this->status == 1;
    }

    /**
     * @return int
     */
    public function isRunning(): int {
        return $this->status == 2;
    }
}
