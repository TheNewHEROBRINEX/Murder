<?php

namespace CastleGames\Murder;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\Player;

class MurderListener implements Listener {

    /** @var MurderMain $plugin */
    private $plugin;

    /** @var array $setspawns */
    public $setspawns;

    public function __construct(MurderMain $plugin) {
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
            $spawns = $this->plugin->getArenasCfg()->get($world, []);
            $spawns[] = array($x, $y, $z);
            $this->plugin->getArenasCfg()->set($world, $spawns);
            $this->plugin->sendMessage("§eSpawn Murder del mondo $world settato a§f $x $y $z. " . ((--$this->setspawns[$name][$world] == 1) ? "§eRimane§f " : "§eRimangono§f ") . $this->setspawns[$name][$world] . " §espawn da settare", $player);
            if ($this->setspawns[$name][$world] <= 0) {
                unset($this->setspawns[$name][$world]);
                $this->plugin->getArenasCfg()->save();
                $this->plugin->addArena($world);
            }
        }
    }

    public function onSwordHoeShoot(DataPacketReceiveEvent $event) {
        if ($this->plugin->getArenaByPlayer($event->getPlayer()) && ($packet = $event->getPacket()) instanceof UseItemPacket && $packet->face === 0xff) {
            $player = $event->getPlayer();
            $item = $player->getInventory()->getItemInHand();
            if ($item->getId() === $item::WOODEN_SWORD || $item->getId() === $item::WOODEN_HOE) {
                $nbt = new CompoundTag("", [
                    "Pos" => new ListTag("Pos", [
                        new DoubleTag("", $player->x),
                        new DoubleTag("", $player->y + $player->getEyeHeight()),
                        new DoubleTag("", $player->z)
                    ]),
                    "Motion" => new ListTag("Motion", [
                        new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
                        new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
                        new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
                    ]),
                    "Rotation" => new ListTag("Rotation", [
                        new FloatTag("", $player->yaw),
                        new FloatTag("", $player->pitch)
                    ]),
                    "Fire" => new ShortTag("Fire", 0)
                ]);
                $arrow = Entity::createEntity("Arrow", $player->chunk, $nbt, $player, true);
                $arrow->setMotion($arrow->getMotion()->multiply(2));
                $arrow->spawnToAll();
                $player->getLevel()->addSound(new LaunchSound($player), $player->getLevel()->getPlayers());
                $event->setCancelled(true);
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        if ($arena = $this->plugin->getArenaByPlayer($player = $event->getPlayer()))
            $arena->quit($player);
    }

    public function onDamage(EntityDamageEvent $event){
        if (($player = $event->getEntity()) instanceof Player && $this->plugin->getArenaByPlayer($player) !== null){
            if ($event instanceof EntityDamageByEntityEvent){

            }
        }
    }
}
