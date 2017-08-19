<?php
namespace TheNewHEROBRINE\Murder;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
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
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\UseItemPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use TheNewHEROBRINE\Murder\entities\Corpse;
use TheNewHEROBRINE\Murder\entities\MurderPlayer;
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
    public function onArenaSetting(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
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
            $this->getPlugin()->sendMessage("§eThe spawn for the §ec$world §eeword has been set at these coordniates §f $x $y $z. " . ((--$this->setspawns[$name][$world] == 1) ? "§eRimane§f " : "§eRimangono§f ") . $this->setspawns[$name][$world] . " §espawn da settare", $player);
            if ($this->setspawns[$name][$world] <= 0){
                unset($this->setspawns[$name][$world]);
                $this->getPlugin()->getArenasCfg()->save();
                $this->getPlugin()->sendMessage("§eSettings of§f {$this->setespawns[$name][$world]} §eEmerlad spawner§f {$player->getLevel()->getFolderName()} §einiziato", $player);
            }
            return;
        }
        if (isset($this->setespawns[$name][$world])){
            $espawns = $this->getPlugin()->getArenasCfg()->getNested("$world.espawns");
            $espawns[] = [$x, $y, $z];
            $this->getPlugin()->getArenasCfg()->setNested("$world.espawns", $espawns);
            $this->getPlugin()->sendMessage("§eEmerald spawner has been set at these coordniates: a§f $x $y $z. " . ((--$this->setespawns[$name][$world] == 1) ? "§eRimane§f " : "§eRimangono§f ") . $this->setespawns[$name][$world] . "§e emerald spawn da settare", $player);
            if ($this->setespawns[$name][$world] <= 0){
                unset($this->setespawns[$name][$world]);
                $this->getPlugin()->getArenasCfg()->save();
            }
            $this->getPlugin()->addArena($world, $this->getPlugin()->getArenasCfg()->getNested("$world.spawns"), $this->getPlugin()->getArenasCfg()->getNested("$world.espawns"));
        }
    }
    /**
     * @param DataPacketReceiveEvent $event
     */
    public function onShoot(DataPacketReceiveEvent $event) {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if ($this->getPlugin()->getArenaByPlayer($player) and ($packet instanceof UseItemPacket and $packet->face === -1 or $packet instanceof InteractPacket and $packet->action === InteractPacket::ACTION_RIGHT_CLICK) and ($item = $player->getInventory()->getItemInHand())->getId() === $item::WOODEN_SWORD || $item->getId() === $item::FISHING_ROD){
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
            $projectile = Entity::createEntity($item->getId() == $item::FISHING_ROD ? "MurderGunProjectile" : "MurderKnifeProjectile", $player->level, $nbt, $player);
            $projectile->setMotion($projectile->getMotion()->multiply(2.5));
            $projectile->spawnToAll();
            $player->getLevel()->addSound(new LaunchSound($player), $player->getLevel()->getPlayers());
            if ($item->getId() == $item::WOODEN_SWORD){
                $player->getInventory()->setItemInHand(Item::get(Item::AIR));
            }
            $event->setCancelled(true);
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
                $this->getPlugin()->sendMessage("You have found an emerald! " . TextFormat::GREEN . "($count/5)", $player);
                if ($count == 5 and !$inv->contains(Item::get(Item::FISHING_ROD, -1, 1))){
                    if ($arena->isBystander($player)){
                        $inv->addItem($item = Item::get(Item::FISHING_ROD)->setCustomName("Pistola"));
                        $this->getPlugin()->sendMessage("You got the gun!", $player);
                    }
                    elseif ($arena->isMurderer($player)){
                        $inv->addItem($item = Item::get(Item::WOODEN_SWORD)->setCustomName("Coltello"));
                        $this->getPlugin()->sendMessage("You got the knife!", $player);
                    }
                    $inv->setHotbarSlotIndex(0, $inv->first($item));
                    $inv->removeItem(Item::get(Item::EMERALD, -1, 4));
                    $inv->sendContents($player);
                    $event->setCancelled();
                    $event->getItem()->kill();
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
        if ($arena = $this->getPlugin()->getArenaByPlayer($player = $event->getPlayer())){
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
                    Entity::createEntity("Corpse", $damaged->getLevel(), new CompoundTag(), $damaged)->spawnToAll();
                    $damaged->setHealth(0);
                }
                //do this only if the player is damaged by a projectile (a bystander's gun shoot or a thrown murderer's sword)
                elseif ($cause == EntityDamageEvent::CAUSE_PROJECTILE){
                    //if a bystander hits the murderer or another bystander
                    if ($arena->isBystander($damager)){
                        //murderer
                        if ($arena->isMurderer($damaged)){
                            $arena->broadcastMessage(TextFormat::BLUE . $damager->getMurderName() . TextFormat::WHITE . " has killed the murderer: " . TextFormat::BLUE . $damaged->getMurderName() . TextFormat::WHITE . "!");
                        }
                        //bystander
                        else{
                            $arena->broadcastMessage(TextFormat::BLUE . $damager->getDisplayName() . TextFormat::WHITE . " killed an innocent person!");
                            $damager->getInventory()->remove(Item::get(Item::FISHING_ROD));
                            $damager->addEffect(Effect::getEffect(Effect::BLINDNESS)->setDuration(20 * 20));
                        }
                    }
                    Entity::createEntity("Corpse", $damaged->getLevel(), new CompoundTag(), $damaged)->spawnToAll();
                    $damaged->setHealth(0);
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
