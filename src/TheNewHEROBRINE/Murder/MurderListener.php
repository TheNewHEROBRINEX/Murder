<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\Player;
use TheNewHEROBRINE\Murder\entity\Corpse;
use function count;

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
		$item = $player->getInventory()->getItemInHand();
		if($this->getPlugin()->getArenaByPlayer($player) !== null and $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR and ($item->getId() === ItemIds::WOODEN_SWORD or $item->getId() === ItemIds::WOODEN_HOE)){
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
		}else{
			$x = $event->getBlock()->getX();
			$y = $event->getBlock()->getFloorY() + 1;
			$z = $event->getBlock()->getZ();
			$world = $player->getLevel()->getFolderName();
			$playerName = $player->getName();
			if(isset($this->setspawns[$playerName][$world])){
				$spawns = $this->getPlugin()->getArenasCfg()->getNested("$world.spawns");
				$spawns[] = [$x, $y, $z];
				$this->getPlugin()->getArenasCfg()->setNested("$world.spawns", $spawns);
				$this->setspawns[$playerName][$world]--;
				$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.playersSpawns.spawnSet", [$world, $x, $y, $z, $this->setspawns[$playerName][$world]]), $player);
				if($this->setspawns[$playerName][$world] === 0){
					unset($this->setspawns[$playerName][$world]);
					if(count($this->setspawns[$playerName]) === 0){
						unset($this->setspawns[$playerName]);
					}
					$this->getPlugin()->getArenasCfg()->save();
					$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.emeraldsSpawns.started", [$this->setespawns[$playerName][$world], $player->getLevel()->getFolderName()]), $player);
				}
			}elseif(isset($this->setespawns[$playerName][$world])){
				$espawns = $this->getPlugin()->getArenasCfg()->getNested("$world.espawns");
				$espawns[] = [$x, $y, $z];
				$this->getPlugin()->getArenasCfg()->setNested("$world.espawns", $espawns);
				$this->setespawns[$playerName][$world]--;
				$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.emeraldsSpawns.spawnSet", [$world, $x, $y, $z, $this->setespawns[$playerName][$world]]), $player);
				if($this->setespawns[$playerName][$world] === 0){
					unset($this->setespawns[$playerName][$world]);
					if(count($this->setespawns[$playerName]) === 0){
						unset($this->setespawns[$playerName]);
					}
					$this->getPlugin()->getArenasCfg()->save();
				}
				$this->getPlugin()->addArena($world, $this->getPlugin()->getArenasCfg()->getNested("$world.spawns"), $this->getPlugin()->getArenasCfg()->getNested("$world.espawns"));
			}
		}
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$arena = $this->getPlugin()->getArenaByPlayer($player);
		if($arena !== null){
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
		if($this->getPlugin()->getArenaByPlayer($event->getPlayer()) !== null){
			$event->setCancelled();
		}
	}

	public function onExhaust(PlayerExhaustEvent $event) : void{
		$player = $event->getPlayer();
		if($player instanceof Player and $this->getPlugin()->getArenaByPlayer($player) !== null){
			$event->setCancelled();
		}
	}

	public function onDamage(EntityDamageEvent $event) : void{
		$damaged = $event->getEntity();
		//players can't hit corpses
		if($damaged instanceof Corpse){
			$event->setCancelled();
		}elseif($damaged instanceof Player){
			$arena = $this->getPlugin()->getArenaByPlayer($damaged);
			if($arena !== null){ //do this only for players that are currently playing murder
				if($arena->isRunning() and $event instanceof EntityDamageByEntityEvent){ //do this only if player is damaged by another one while in game
					$damager = $event->getDamager();
					if($damager instanceof Player){
						$cause = $event->getCause();
						//if player is directly attacked by the murderer with the knife
						if($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK and $arena->isMurderer($damager) and $damager->getInventory()->getItemInHand()->getId() === ItemIds::WOODEN_SWORD){
							$arena->spawnCorpse($damaged);
							$arena->quit($damaged, true);
							$damaged->sendTitle($this->getPlugin()->translateString("game.death.title"), $this->getPlugin()->translateString("game.death.subtitle", [$damager->getName()]));
						}elseif($cause === EntityDamageEvent::CAUSE_PROJECTILE){ //do this only if the player is damaged by a projectile (a bystander's gun shoot or a thrown murderer's knife)
							//if a bystander hits the murderer or another bystander
							if($arena->isBystander($damager)){
								if($arena->isMurderer($damaged)){ //the murderer
									$arena->broadcastMessage($this->getPlugin()->translateString("game.kill.murderer", [$arena->getFullName($damager), $arena->getFullName($damaged)]));
									$damaged->setLastDamageCause($event);
								}else{ //a bystander
									$arena->broadcastMessage($this->getPlugin()->translateString("game.kill.bystander", [$damager->getDisplayName()]));
									$damager->getInventory()->remove(ItemFactory::get(ItemIds::WOODEN_HOE));
									$damager->addEffect((new EffectInstance(Effect::getEffect(Effect::BLINDNESS)))->setDuration(20 * 20));
									$arena->spawnCorpse($damaged);
								}
							}
							$arena->quit($damaged, true);
							$damaged->sendTitle($this->getPlugin()->translateString("game.death.title"), $this->getPlugin()->translateString("game.death.subtitle", [$damager->getName()]));
						}
					}
				}
				//prevent other types of damage
				$event->setCancelled();
			}
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