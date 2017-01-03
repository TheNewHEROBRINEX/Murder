<?php

namespace emanuele0204\am_Murder;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\level\LevelEvent;
use pocketmine\event\DropLevelEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    private $config, $setspawns;
    
    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->config = new Config($this->getDataFolder() . "config.yml");
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool {
        if ($sender instanceof Player) {
            if (isset($args[0])) {
                switch (array_shift($args)) {
                    case "join":
                        //TODO
                        break;
                    case "quit":
                        //TODO
                        break;
                    case "setspawns":
                        $world = $sender->getLevel()->getName();
                        $name = $sender->getName();
                        if ($sender->hasPermission("murder.command.setspawns"))
                            if (isset($args[0]) && is_numeric($args[0])) {
                                $this->setspawns[$name][$world] = (int)$args[0];
                                $this->getConfig()->remove($world);
                                $sender->sendMessage("§eSettaggio di§f $args[0] §espawn per il mondo§f {$sender->getLevel()->getName()} §einiziato");
                            }
                        break;
                }
            }
        }
        return true;
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
            $spawns = $this->getConfig()->get($world, []);
            $spawns[] = array($x, $y, $z);
            $this->getConfig()->set($world, $spawns);
            --$this->setspawns[$name][$world];
            $player->sendMessage("§eSpawn Murder del mondo $world settato a§f $x $y $z. " . (($this->setspawns[$name][$world] == 1) ? "§eRimane " : "§eRimangono ") . $this->setspawns[$name][$world] . " §espawn da settare");
            if ($this->setspawns[$name][$world] <= 0) {
                unset($this->setspawns[$name][$world]);
                $this->getConfig()->save();
            }
        }
    }
}
