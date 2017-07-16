<?php

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\UseItemPacket;
use pocketmine\Player;

class MurderListener implements Listener {

    /** @var array $setspawns */
    public $setspawns;
    /** @var array $setespawns */
    public $setespawns;
    /** @var MurderMain $plugin */
    private $plugin;

    public function __construct(MurderMain $plugin) {
        $this->plugin = $plugin;
    }

    public function onArenaSetting(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $world = $player->getLevel()->getName();
        $name = $player->getName();
        $block = $event->getBlock();
        $x = $block->getX();
        $y = $block->getFloorY() + 1;
        $z = $block->getZ();
        if (isset($this->setspawns[$name][$world])) {
            $spawns = $this->plugin->getArenasCfg()->getNested("$world.spawns");
            $spawns[] = array($x, $y, $z);
            $this->plugin->getArenasCfg()->setNested("$world.spawns", $spawns);
            $this->plugin->sendMessage("§eSpawn Murder del mondo §f$world §esettato a§f $x $y $z. " . ((--$this->setspawns[$name][$world] == 1) ? "§eRimane§f " : "§eRimangono§f ") . $this->setspawns[$name][$world] . " §espawn da settare", $player);
            if ($this->setspawns[$name][$world] <= 0) {
                unset($this->setspawns[$name][$world]);
                $this->plugin->getArenasCfg()->save();
                $this->plugin->sendMessage("§eSettaggio di§f {$this->setespawns[$name][$world]} §eemerald spawn per il mondo§f {$player->getLevel()->getName()} §einiziato", $player);
            }
            return;
        }

        if (isset($this->setespawns[$name][$world])) {
            $espawns = $this->plugin->getArenasCfg()->getNested("$world.espawns");
            $espawns[] = array($x, $y, $z);
            $this->plugin->getArenasCfg()->setNested("$world.espawns", $espawns);
            $this->plugin->sendMessage("§eEmerald spawn Murder del mondo §f$world §esettato a§f $x $y $z. " . ((--$this->setespawns[$name][$world] == 1) ? "§eRimane§f " : "§eRimangono§f ") . $this->setespawns[$name][$world] . "§e emerald spawn da settare", $player);
            if ($this->setespawns[$name][$world] <= 0) {
                unset($this->setespawns[$name][$world]);
                $this->plugin->getArenasCfg()->save();
            }
            $this->plugin->addArena($world, $this->plugin->getArenasCfg()->getNested("$world.spawns"), $this->plugin->getArenasCfg()->getNested("$world.espawns"));
        }

    }

    public function onSwordHoeShoot(DataPacketReceiveEvent $event) {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if ($this->plugin->getArenaByPlayer($player) and $packet instanceof UseItemPacket and $packet->face === -1 and ($item = $player->getInventory()->getItemInHand())->getId() === $item::WOODEN_SWORD || $item->getId() === $item::WOODEN_HOE) {
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
            ]);

            $projectile = Entity::createEntity($item->getId() == $item::WOODEN_HOE ? "MurderGunProjectile" : "MurderKnifeProjectile", $player->level, $nbt, $player);
            $projectile->setMotion($projectile->getMotion()->multiply(2));
            $projectile->spawnToAll();
            $player->getLevel()->addSound(new LaunchSound($player), $player->getLevel()->getPlayers());
            if ($item->getId() == $item::WOODEN_SWORD)
                $player->getInventory()->remove($player->getInventory()->getItemInHand());
            $event->setCancelled(true);
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        if ($arena = $this->plugin->getArenaByPlayer($player = $event->getPlayer()))
            $arena->quit($player);
    }

    public function onEmeraldPickup(InventoryPickupItemEvent $event) {
        $inv = $event->getInventory();
        $player = $inv->getHolder();
        $item = $event->getItem()->getItem();
        if ($player instanceof Player and $arena = $this->plugin->getArenaByPlayer($player) and $item->getId() == Item::EMERALD and $inv->contains(Item::get(Item::EMERALD, -1, 4))) {
            if ($arena->isBystander($player) and !$inv->contains(Item::get(Item::WOODEN_HOE, -1, 1))) {
                $inv->addItem(Item::get(Item::WOODEN_HOE)->setCustomName("Pistola"));
                $this->plugin->sendMessage("Hai ricevuto una pistola!", $player);
            } elseif ($arena->isMurderer($player)) {
                $inv->addItem(Item::get(Item::WOODEN_SWORD)->setCustomName("Coltello"));
                $this->plugin->sendMessage("Hai ricevuto un altro coltello!", $player);
            }
            $inv->remove(Item::get(Item::EMERALD));
            $event->setCancelled();
            $event->getItem()->kill();
            $inv->sendContents($player);
        }
    }

    public function onDeath(PlayerDeathEvent $event){
        if ($this->plugin->getArenaByPlayer($event->getPlayer()))
            $event->setDrops([]);
    }

    public function onDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        if ($player instanceof Player && $this->plugin->getArenaByPlayer($player) !== null) {
            if ($event instanceof EntityDamageByEntityEvent) {

            }
        }
    }
}