<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\Player;
use TheNewHEROBRINE\Murder\entity\Corpse;

class MurderListener implements Listener{

	/** @var int[][] */
	public $setspawns;

	/** @var int[][] */
	public $setespawns;

	/** @var MurderMain */
	private $plugin;

	public function __construct(MurderMain $plugin){
		$this->plugin = $plugin;
	}

	public function onInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		if($this->getPlugin()->getArenaByPlayer($player) and $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR and ($item = $player->getInventory()->getItemInHand())->getId() === ItemIds::WOODEN_SWORD || $item->getId() === ItemIds::WOODEN_HOE){
			$nbt = Entity::createBaseNBT(
				$player->add(0, $player->getEyeHeight(), 0),
				$player->getDirectionVector(),
				($player->yaw > 180 ? 360 : 0) - $player->yaw,
				-$player->pitch
			);
			$projectile = Entity::createEntity($item->getId() === ItemIds::WOODEN_HOE ? "MurderGunProjectile" : "MurderKnifeProjectile", $player->level, $nbt, $player);
			$projectile->setMotion($projectile->getMotion()->multiply(2.5));
			$projectile->spawnToAll();
			if($item->getId() === ItemIds::WOODEN_SWORD){
				$player->getInventory()->setItemInHand(ItemFactory::get(ItemIds::AIR));
				$pk = new AddActorPacket();
				$pk->entityRuntimeId = Entity::$entityCount;
				$pk->type = AddActorPacket::LEGACY_ID_MAP_BC[EntityIds::CREEPER];
				$pk->position = $player->asVector3();
				$pk->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_INVISIBLE]];
				$player->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
				$pk = new ActorEventPacket();
				$pk->entityRuntimeId = Entity::$entityCount;
				$pk->event = ActorEventPacket::HURT_ANIMATION;
				$player->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
				$pk = new RemoveActorPacket();
				$pk->entityUniqueId = Entity::$entityCount;
				$player->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
			}else{
				$player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_EXPLODE);
			}
			$event->setCancelled();
			return;
		}

		$world = $player->getLevel()->getFolderName();
		$name = $player->getName();
		$block = $event->getBlock();
		$x = $block->getX();
		$y = $block->getFloorY() + 1;
		$z = $block->getZ();
		if(isset($this->setspawns[$name][$world])){
			$spawns = $this->getPlugin()->getArenasCfg()->getNested("$world.spawns");
			$spawns[] = [$x, $y, $z];
			$this->getPlugin()->getArenasCfg()->setNested("$world.spawns", $spawns);
			$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.playersSpawns.spawnSet", [$world, $x, $y, $z, --$this->setspawns[$name][$world]]), $player);
			if($this->setspawns[$name][$world] <= 0){
				unset($this->setspawns[$name][$world]);
				$this->getPlugin()->getArenasCfg()->save();
				$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.emeraldsSpawns.started", [$this->setespawns[$name][$world], $player->getLevel()->getFolderName()]), $player);
			}
			return;
		}

		if(isset($this->setespawns[$name][$world])){
			$espawns = $this->getPlugin()->getArenasCfg()->getNested("$world.espawns");
			$espawns[] = [$x, $y, $z];
			$this->getPlugin()->getArenasCfg()->setNested("$world.espawns", $espawns);
			$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.emeraldsSpawns.spawnSet", [$world, $x, $y, $z, --$this->setespawns[$name][$world]]), $player);
			if($this->setespawns[$name][$world] <= 0){
				unset($this->setespawns[$name][$world]);
				$this->getPlugin()->getArenasCfg()->save();
			}
			$this->getPlugin()->addArena($world, $this->getPlugin()->getArenasCfg()->getNested("$world.spawns"), $this->getPlugin()->getArenasCfg()->getNested("$world.espawns"));
		}

	}

	public function onQuit(PlayerQuitEvent $event) : void{
		if($arena = $this->getPlugin()->getArenaByPlayer($player = $event->getPlayer())){
			$arena->quit($player);
		}
	}

	public function onItemPickup(InventoryPickupItemEvent $event) : void{
		$inventory = $event->getInventory();
		if($inventory instanceof PlayerInventory){
			$player = $inventory->getHolder();
			if($player instanceof Player){
				$arena = $this->getPlugin()->getArenaByPlayer($player);
				$item = $event->getItem()->getItem();
				if($arena instanceof MurderArena and $item->getId() === ItemIds::EMERALD){
					$emeraldCount = 0;
					/** @var Item $slot */
					foreach($player->getInventory()->all(ItemFactory::get(ItemIds::EMERALD, -1)) as $slot){
						$emeraldCount += $slot->getCount();
					}
					$emeraldCount += 1;
					$this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.emerald", [$emeraldCount]), $player);
					if($emeraldCount === 5 and !$inventory->contains(ItemFactory::get(ItemIds::WOODEN_HOE, -1))){
						if($arena->isBystander($player)){
							$inventory->addItem($item = ItemFactory::get(ItemIds::WOODEN_HOE)->setCustomName($this->getPlugin()->translateString("game.gun")));
							$this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.gun"), $player);
						}elseif($arena->isMurderer($player)){
							$inventory->addItem($item = ItemFactory::get(ItemIds::WOODEN_SWORD)->setCustomName($this->getPlugin()->translateString("game.knife")));
							$this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.knife"), $player);
						}
						$inventory->equipItem(0);
						$inventory->removeItem(ItemFactory::get(ItemIds::EMERALD, -1, 4));
						$inventory->sendContents($player);
						$event->setCancelled();
						$event->getItem()->flagForDespawn();
					}
				}elseif($item->getId() === ItemIds::WOODEN_SWORD and $arena->isBystander($player)){
					$event->setCancelled();
				}
			}
		}
	}

	public function onItemDrop(PlayerDropItemEvent $event) : void{
		if($this->getPlugin()->getArenaByPlayer($event->getPlayer())){
			$event->setCancelled();
		}
	}

	public function onExhaust(PlayerExhaustEvent $event) : void{
		$player = $event->getPlayer();
		if($player instanceof Player and $this->getPlugin()->getArenaByPlayer($player)){
			$event->setCancelled();
		}
	}

	public function onDeath(PlayerDeathEvent $event) : void{
		if($arena = $this->getPlugin()->getArenaByPlayer($player = $event->getPlayer()) and $arena->isRunning()){
			$nbt = Entity::createBaseNBT($player, null, $player->yaw, $player->pitch);
			$player->saveNBT();
			$nbt->setTag(clone $player->namedtag->getTag("Inventory"));
			$nbt->setTag(new CompoundTag("Skin", ["Data" => new StringTag("Data", $player->getSkin()->getSkinData()), "Name" => new StringTag("Name", $player->getSkin()->getSkinId())]));
			/** @var Corpse $corpse */
			$corpse = Entity::createEntity("Corpse", $player->getLevel(), $nbt);
			$corpse->getDataPropertyManager()->setBlockPos(Human::DATA_PLAYER_BED_POSITION, $player->floor());
			$corpse->setDataFlag(Human::DATA_PLAYER_FLAGS, Human::DATA_PLAYER_FLAG_SLEEP, true, Human::DATA_TYPE_BYTE);
			$corpse->spawnToAll();
			$arena->quit($player, true);
			$event->setDrops([]);
			$event->setDeathMessage("");
		}
	}

	public function onDamage(EntityDamageEvent $event) : void{
		//players can't hit corpses
		if(($damaged = $event->getEntity()) instanceof Corpse){
			$event->setCancelled();
		}//do this only for players that are currently playing murder
		elseif($damaged instanceof Player and $arena = $this->getPlugin()->getArenaByPlayer($damaged)){
			//do this only if player is damaged by another one while in game
			if($arena->isRunning() and $event instanceof EntityDamageByEntityEvent and ($damager = $event->getDamager()) instanceof Player){
				/** @var Player $damager */
				//if player is attacked directly by the murderer using a wooden sword
				if(($cause = $event->getCause()) === EntityDamageEvent::CAUSE_ENTITY_ATTACK and $arena->isMurderer($damager) and $damager->getInventory()->getItemInHand()->getId() == ItemIds::WOODEN_SWORD){
					$damaged->setHealth(0);
					$damaged->sendTitle($this->getPlugin()->translateString("game.death.title"), $this->getPlugin()->translateString("game.death.subtitle", [$damager->getName()]));
				}//do this only if the player is damaged by a projectile (a bystander's gun shoot or a thrown murderer's sword)
				elseif($cause === EntityDamageEvent::CAUSE_PROJECTILE){
					//if a bystander hits the murderer or another bystander
					if($arena->isBystander($damager)){
						//murderer
						if($arena->isMurderer($damaged)){
							$arena->broadcastMessage($this->getPlugin()->translateString("game.kill.murderer", [$arena->getFullName($damager), $arena->getFullName($damaged)]));
							$damaged->setLastDamageCause($event);
						}//bystander
						else{
							$arena->broadcastMessage($this->getPlugin()->translateString("game.kill.bystander", [$damager->getDisplayName()]));
							$damager->getInventory()->remove(ItemFactory::get(ItemIds::WOODEN_HOE));
							$damager->addEffect((new EffectInstance(Effect::getEffect(Effect::BLINDNESS)))->setDuration(20 * 20));
						}
					}
					$damaged->setHealth(0);
					$damaged->sendTitle($this->getPlugin()->translateString("game.death.title"), $this->getPlugin()->translateString("game.death.subtitle", [$damager->getName()]));
				}
			}
			//prevent other types of damage
			$event->setCancelled();
		}
	}

	public function onLevelChange(EntityLevelChangeEvent $event) : void{
		$entity = $event->getEntity();
		$target = $event->getTarget();
		if($entity instanceof Player){
			$arena = $this->getPlugin()->getArenaByPlayer($entity);
			if($arena and $target !== $arena->getWorld() and $target !== $this->getPlugin()->getHub()){
				$arena->quit($entity);
			}
		}
	}

	public function getPlugin() : MurderMain{
		return $this->plugin;
	}
}