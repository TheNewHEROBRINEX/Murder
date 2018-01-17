<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\Player;
use TheNewHEROBRINE\Murder\entity\Corpse;
use TheNewHEROBRINE\Murder\entity\MurderPlayer;

class MurderListener implements Listener {

    /** @var array $setspawns */
    public $setspawns;
    /** @var array $setespawns */
    public $setespawns;
    /** @var MurderMain $plugin */
    private $plugin;

    /**
     * @param MurderMain $plugin
     */
    public function __construct(MurderMain $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        if ($this->getPlugin()->getArenaByPlayer($player) and $event->getAction() == PlayerInteractEvent::RIGHT_CLICK_AIR and ($item = $player->getInventory()->getItemInHand())->getId() === $item::WOODEN_SWORD || $item->getId() === $item::WOODEN_HOE) {
            $nbt = Entity::createBaseNBT(
                $player->add(0, $player->getEyeHeight(), 0),
                $player->getDirectionVector(),
                ($player->yaw > 180 ? 360 : 0) - $player->yaw,
                -$player->pitch
            );
            $projectile = Entity::createEntity($item->getId() == $item::WOODEN_HOE ? "MurderGunProjectile" : "MurderKnifeProjectile", $player->level, $nbt, $player);
            $projectile->setMotion($projectile->getMotion()->multiply(2.5));
            $projectile->spawnToAll();
            if ($item->getId() == $item::WOODEN_SWORD){
                $player->getInventory()->setItemInHand(Item::get(Item::AIR));
                $pk = new AddEntityPacket();
                $pk->entityRuntimeId = Entity::$entityCount;
                $pk->type = 33;
                $pk->position = $player->asVector3();
                $pk->motion = new Vector3(0, 0, 0);
                $pk->yaw = 0;
                $pk->pitch = 0;
                $pk->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_INVISIBLE]];
                $player->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
                $pk = new EntityEventPacket();
                $pk->entityRuntimeId = Entity::$entityCount;
                $pk->event = EntityEventPacket::HURT_ANIMATION;
                $pk->data = 0;
                $player->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
                $pk = new RemoveEntityPacket();
                $pk->entityUniqueId = Entity::$entityCount;
                $player->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
            }else {
                $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_EXPLODE);
            }
            $event->setCancelled(true);
            return;
        }
        $world = $player->getLevel()->getFolderName();
        $name = $player->getName();
        $block = $event->getBlock();
        $x = $block->getX();
        $y = $block->getFloorY() + 1;
        $z = $block->getZ();
        if (isset($this->setspawns[$name][$world])){
            $spawns = $this->getPlugin()->getArenasCfg()->getNested("$world.spawns");
            $spawns[] = [$x, $y, $z];
            $this->getPlugin()->getArenasCfg()->setNested("$world.spawns", $spawns);
            $this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.playersSpawns.spawnSet", [$world, $x, $y, $z, --$this->setspawns[$name][$world]]), $player);
            if ($this->setspawns[$name][$world] <= 0){
                unset($this->setspawns[$name][$world]);
                $this->getPlugin()->getArenasCfg()->save();
                $this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.emeraldsSpawns.started", [$this->setespawns[$name][$world], $player->getLevel()->getFolderName()]), $player);
            }
            return;
        }

        if (isset($this->setespawns[$name][$world])){
            $espawns = $this->getPlugin()->getArenasCfg()->getNested("$world.espawns");
            $espawns[] = [$x, $y, $z];
            $this->getPlugin()->getArenasCfg()->setNested("$world.espawns", $espawns);
            $this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.emeraldsSpawns.spawnSet", [$world, $x, $y, $z, --$this->setespawns[$name][$world]]), $player);
            if ($this->setespawns[$name][$world] <= 0){
                unset($this->setespawns[$name][$world]);
                $this->getPlugin()->getArenasCfg()->save();
            }
            $this->getPlugin()->addArena($world, $this->getPlugin()->getArenasCfg()->getNested("$world.spawns"), $this->getPlugin()->getArenasCfg()->getNested("$world.espawns"));
        }

    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event) {
        if ($arena = $this->getPlugin()->getArenaByPlayer($player = $event->getPlayer())){
            $arena->quit($player);
        }
    }

    /**
     * @param InventoryPickupItemEvent $event
     */
    public function onItemPickup(InventoryPickupItemEvent $event) {
        $player = $event->getInventory()->getHolder();
        $item = $event->getItem()->getItem();
        if ($player instanceof MurderPlayer and $arena = $this->getPlugin()->getArenaByPlayer($player)) {
            if ($item->getId() == Item::EMERALD) {
                $inv = $player->getInventory();
                $count = $player->getItemCount() + 1;
                $this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.emerald", [$count]), $player);
                if ($count == 5 and !$inv->contains(Item::get(Item::WOODEN_HOE, -1, 1))){
                    if ($arena->isBystander($player)){
                        $inv->addItem($item = Item::get(Item::WOODEN_HOE)->setCustomName($this->getPlugin()->translateString("game.gun")));
                        $this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.gun"), $player);
                    }
                    elseif ($arena->isMurderer($player)){
                        $inv->addItem($item = Item::get(Item::WOODEN_SWORD)->setCustomName($this->getPlugin()->translateString("game.knife")));
                        $this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.knife"), $player);
                    }
                    $inv->equipItem(0);
                    $inv->removeItem(Item::get(Item::EMERALD, -1, 4));
                    $inv->sendContents($player);
                    $event->setCancelled();
                    $event->getItem()->flagForDespawn();
                }
            }
            
            elseif ($item->getId() == Item::WOODEN_SWORD and $arena->isBystander($player)) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     */
    public function onItemDrop(PlayerDropItemEvent $event){
        if ($this->getPlugin()->getArenaByPlayer($event->getPlayer())){
            $event->setCancelled();
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     */
    public function onExhaust(PlayerExhaustEvent $event){
        $player = $event->getPlayer();
        if ($player instanceof Player and $this->getPlugin()->getArenaByPlayer($player)){
            $event->setCancelled();
        }
    }

    /**
     * @param PlayerDeathEvent $event
     */
    public function onDeath(PlayerDeathEvent $event) {
        if ($arena = $this->getPlugin()->getArenaByPlayer($player = $event->getPlayer()) and $arena->isRunning()){
            $nbt = Entity::createBaseNBT($player, null, $player->yaw, $player->pitch);
            $player->saveNBT();
            $nbt->Inventory = clone $player->namedtag->Inventory;
            $nbt->Skin = new CompoundTag("Skin", ["Data" => new StringTag("Data", $player->getSkin()->getSkinData()), "Name" => new StringTag("Name", $player->getSkin()->getSkinId())]);
            $corpse = Entity::createEntity("Corpse", $player->getLevel(), $nbt);
            $corpse->setDataProperty(Human::DATA_PLAYER_BED_POSITION, Human::DATA_TYPE_POS, [(int)$player->x, (int)$player->y, (int)$player->z]);
            $corpse->setDataFlag(Human::DATA_PLAYER_FLAGS, Human::DATA_PLAYER_FLAG_SLEEP, true, Human::DATA_TYPE_BYTE);
            $corpse->spawnToAll();
            $arena->quit($player, true);
            $event->setDrops([]);
            $event->setDeathMessage("");
        }
    }

    /**
     * don't try to read this if you don't like long and nested ifs
     * @param EntityDamageEvent $event
     */
    public function onDamage(EntityDamageEvent $event) {
        //players can't hit corpses
        if (($damaged = $event->getEntity()) instanceof Corpse){
            $event->setCancelled();
        }
        //do this only for players that are currently playing murder
        elseif ($damaged instanceof MurderPlayer and $arena = $this->getPlugin()->getArenaByPlayer($damaged)){
            //do this only if player is damaged by another one while in game
            if ($arena->isRunning() and $event instanceof EntityDamageByEntityEvent and ($damager = $event->getDamager()) instanceof MurderPlayer){
                /** @var MurderPlayer $damager */
                //if player is attacked directly by the murderer using a wooden sword
                if (($cause = $event->getCause()) == EntityDamageEvent::CAUSE_ENTITY_ATTACK and $arena->isMurderer($damager) and $damager->getInventory()->getItemInHand()->getId() == Item::WOODEN_SWORD){
                    $damaged->setHealth(0);
                    $damaged->addTitle($this->getPlugin()->translateString("game.death.title"), $this->getPlugin()->translateString("game.death.subtitle", [$damager->getName()]));
                }
                //do this only if the player is damaged by a projectile (a bystander's gun shoot or a thrown murderer's sword)
                elseif ($cause == EntityDamageEvent::CAUSE_PROJECTILE){
                    //if a bystander hits the murderer or another bystander
                    if ($arena->isBystander($damager)){
                        //murderer
                        if ($arena->isMurderer($damaged)){
                            $arena->broadcastMessage($this->getPlugin()->translateString("game.kill.murderer", [$damager->getMurderName(), $damaged->getMurderName()]));
                            $damaged->setLastDamageCause($event);
                        }
                        //bystander
                        else{
                            $arena->broadcastMessage($this->getPlugin()->translateString("game.kill.bystander", [$damager->getName()]));
                            $damager->getInventory()->remove(Item::get(Item::WOODEN_HOE));
                            $damager->addEffect(Effect::getEffect(Effect::BLINDNESS)->setDuration(20 * 20));
                        }
                    }
                    $damaged->setHealth(0);
                    $damaged->addTitle($this->getPlugin()->translateString("game.death.title"), $this->getPlugin()->translateString("game.death.subtitle", [$damager->getName()]));
                }
            }
            //prevent other types of damage
            $event->setCancelled();
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     */
    public function onLevelChange(EntityLevelChangeEvent $event){
        $entity = $event->getEntity();
        $target = $event->getTarget();
        if ($entity instanceof Player) {
            $arena = $this->getPlugin()->getArenaByPlayer($entity);
            if ($arena and $target !== $arena->getWorld() and $target !== $this->getPlugin()->getHub()){
                $arena->quit($entity);
            }
        }
    }

    /**
     * @param PlayerCreationEvent $event
     */
    public function onPlayerCreation(PlayerCreationEvent $event) {
        $event->setPlayerClass(MurderPlayer::class);
    }

    /**
     * @return MurderMain
     */
    public function getPlugin(): MurderMain {
        return $this->plugin;
    }
}