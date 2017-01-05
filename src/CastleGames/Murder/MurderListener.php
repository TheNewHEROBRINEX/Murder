<?php

namespace CastleGames\Murder;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;

class MurderListener implements Listener {

	private $plugin;
	public $setspawns;

	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
	}

	public function onSpawnsSetting(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		$world = $player->getLevel()->getName();
		$name = $player->getName();
		$block = $event->getBlock();
		$x = $block->getX();
		$y = $block->getFloorY() + 1;
		$z = $block->getZ();
		if (isset($this->setspawns[$name][$world])) {
			$spawns = $this->plugin->getConfig()->get($world, []);
			$spawns[] = array($x, $y, $z);
			$this->plugin->getConfig()->set($world, $spawns);
			--$this->setspawns[$name][$world];
			$player->sendMessage("§eSpawn Murder del mondo $world settato a§f $x $y $z. " . (($this->setspawns[$name][$world] == 1) ? "§eRimane " : "§eRimangono ") . $this->setspawns[$name][$world] . " §espawn da settare");
			if ($this->setspawns[$name][$world] <= 0) {
				unset($this->setspawns[$name][$world]);
				$this->plugin->getConfig()->save();
			}
		}
	}
}
