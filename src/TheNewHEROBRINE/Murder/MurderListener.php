<?php
declare(strict_types=1);

namespace TheNewHEROBRINE\Murder;

use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\Sword;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\Player;
use TheNewHEROBRINE\Murder\entity\projectile\MurderGunProjectile;
use TheNewHEROBRINE\Murder\entity\projectile\MurderKnifeProjectile;
use TheNewHEROBRINE\Murder\player\MurderPlayer;
use function count;

class MurderListener implements Listener{

	/**
	 * @var int[][]
	 * @phpstan-var array<string, array<string, int>>
	 */
	public array $setspawns;

	/**
	 * @var int[][]
	 * @phpstan-var array<string, array<string, int>>
	 */
	public array $setespawns;

	private MurderMain $plugin;

	public function __construct(MurderMain $plugin){
		$this->plugin = $plugin;
	}

	public function onInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
		$world = $player->getLevelNonNull();
		if($this->getPlugin()->getArenaByPlayer($player) !== null and $event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR and ($item->getId() === ItemIds::WOODEN_SWORD or $item->getId() === ItemIds::WOODEN_HOE)){
			$nbt = Entity::createBaseNBT(
				$player->add(0, $player->getEyeHeight(), 0),
				$player->getDirectionVector(),
				($player->yaw > 180 ? 360 : 0) - $player->yaw,
				-$player->pitch
			);
			/** @var MurderGunProjectile|MurderKnifeProjectile $projectile */
			$projectile = Entity::createEntity($item->getId() === ItemIds::WOODEN_HOE ? "MurderGunProjectile" : "MurderKnifeProjectile", $world, $nbt, $player);
			$projectile->setMotion($projectile->getMotion()->multiply(1.5));
			$projectile->spawnToAll();
			if($item->getId() === ItemIds::WOODEN_SWORD){
				$player->getInventory()->setItemInHand(ItemFactory::get(ItemIds::AIR));
				$pk = new AddActorPacket();
				$pk->entityRuntimeId = Entity::$entityCount;
				$pk->type = AddActorPacket::LEGACY_ID_MAP_BC[EntityIds::CREEPER];
				$pk->position = $player->asVector3();
				$pk->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 1 << Entity::DATA_FLAG_INVISIBLE]];
				$player->getServer()->broadcastPacket($world->getPlayers(), $pk);
				$pk = new ActorEventPacket();
				$pk->entityRuntimeId = Entity::$entityCount;
				$pk->event = ActorEventPacket::HURT_ANIMATION;
				$player->getServer()->broadcastPacket($world->getPlayers(), $pk);
				$pk = new RemoveActorPacket();
				$pk->entityUniqueId = Entity::$entityCount;
				$player->getServer()->broadcastPacket($world->getPlayers(), $pk);
			}else{
				$world->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_EXPLODE);
			}
		}else{
			$x = $event->getBlock()->getX();
			$y = $event->getBlock()->getFloorY() + 1;
			$z = $event->getBlock()->getZ();
			$worldName = $world->getFolderName();
			$playerName = $player->getName();
			if(isset($this->setspawns[$playerName][$worldName])){
				/** @phpstan-var list<array{int, int, int}> $spawns */
				$spawns = $this->getPlugin()->getArenasCfg()->getNested("$worldName.spawns");
				$spawns[] = [$x, $y, $z];
				$this->getPlugin()->getArenasCfg()->setNested("$worldName.spawns", $spawns);
				$this->setspawns[$playerName][$worldName]--;
				$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.playersSpawns.spawnSet", [$worldName, (string)$x, (string)$y, (string)$z, (string)$this->setspawns[$playerName][$worldName]]), $player);
				if($this->setspawns[$playerName][$worldName] === 0){
					unset($this->setspawns[$playerName][$worldName]);
					if(count($this->setspawns[$playerName]) === 0){
						unset($this->setspawns[$playerName]);
					}
					$this->getPlugin()->getArenasCfg()->save();
					$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.emeraldsSpawns.started", [(string)$this->setespawns[$playerName][$worldName], $worldName]), $player);
				}
			}elseif(isset($this->setespawns[$playerName][$worldName])){
				/** @phpstan-var list<array{int, int, int}> $espawns */
				$espawns = $this->getPlugin()->getArenasCfg()->getNested("$worldName.espawns");
				$espawns[] = [$x, $y, $z];
				$this->getPlugin()->getArenasCfg()->setNested("$worldName.espawns", $espawns);
				$this->setespawns[$playerName][$worldName]--;
				$this->getPlugin()->sendMessage($this->getPlugin()->translateString("arenaSetting.emeraldsSpawns.spawnSet", [$worldName, (string)$x, (string)$y, (string)$z, (string)$this->setespawns[$playerName][$worldName]]), $player);
				if($this->setespawns[$playerName][$worldName] === 0){
					unset($this->setespawns[$playerName][$worldName]);
					if(count($this->setespawns[$playerName]) === 0){
						unset($this->setespawns[$playerName]);
					}
					$this->getPlugin()->getArenasCfg()->save();
				}
				$this->getPlugin()->addArena($worldName, $this->getPlugin()->getArenasCfg()->getNested("$worldName.spawns"), $this->getPlugin()->getArenasCfg()->getNested("$worldName.espawns"));
			}
		}
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		//$this->plugin->findMurderPlayer($event->getPlayer())?->onQuit(); PHP8
		$murderPlayer = $this->plugin->findMurderPlayer($event->getPlayer());
		if($murderPlayer instanceof MurderPlayer){
			$murderPlayer->onQuit();
		}
	}

	public function onItemPickup(InventoryPickupItemEvent $event) : void{
		$inventory = $event->getInventory();
		if($inventory instanceof PlayerInventory){
			$player = $inventory->getHolder();
			if($player instanceof Player){
				$murderPlayer = $this->plugin->findMurderPlayer($player);
				if($murderPlayer instanceof MurderPlayer){
					$event->setCancelled();
					$itemEntity = $event->getItem();
					$itemItem = $itemEntity->getItem();
					/*if(match(true){
						$itemItem->getId() === ItemIds::EMERALD => $murderPlayer->onEmeraldPickup(),
						$itemItem instanceof Sword              => $murderPlayer->onSwordPickup($itemItem),
						default                                 => false
					}){
						$pk = new TakeItemActorPacket(); //play sound and despawn as normal
						$pk->eid = $player->getId();
						$pk->target = $itemEntity->getId();
						$this->getPlugin()->getServer()->broadcastPacket($player->getViewers(), $pk);
						$itemEntity->flagForDespawn();
					} PHP8*/
					if($itemItem->getId() === ItemIds::EMERALD){
						$result = $murderPlayer->onEmeraldPickup();
					}elseif($itemItem instanceof Sword){
						$result = $murderPlayer->onSwordPickup($itemItem);
					}else{
						$result = false;
					}
					if($result){
						$pk = new TakeItemActorPacket(); //play sound and despawn as normal
						$pk->eid = $player->getId();
						$pk->target = $itemEntity->getId();
						$this->getPlugin()->getServer()->broadcastPacket($player->getViewers(), $pk);
						$itemEntity->flagForDespawn();
					}
				}
			}
		}
	}/*
					if($itemItem->getId() === ItemIds::EMERALD){
						$emeraldCount = 0;
						/** @var Item $slot *\/
						foreach($player->getInventory()->all(ItemFactory::get(ItemIds::EMERALD, -1)) as $slot){
							$emeraldCount += $slot->getCount();
						}
						$emeraldCount += 1;
						$this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.emerald", [$emeraldCount]), $player);
						if($emeraldCount >= 5 and !$inventory->contains(ItemFactory::get(ItemIds::WOODEN_HOE, -1))){
							if($arena->isBystander($player)){
								$pickedupItem = ItemFactory::get(ItemIds::WOODEN_HOE)->setCustomName($this->getPlugin()->translateString("game.gun"));
								$this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.gun"), $player);
							}else{
								$pickedupItem = ItemFactory::get(ItemIds::WOODEN_SWORD)->setCustomName($this->getPlugin()->translateString("game.knife"));
								$this->getPlugin()->sendMessage($this->getPlugin()->translateString("game.found.knife"), $player);
							}
							$inventory->removeItem(ItemFactory::get(ItemIds::EMERALD, -1, 4));
							$inventory->addItem($pickedupItem);
							$event->setCancelled();
							$pk = new TakeItemActorPacket(); //play sound and despawn anyway
							$pk->eid = $player->getId();
							$pk->target = $itemEntity->getId();
							$this->getPlugin()->getServer()->broadcastPacket($player->getViewers(), $pk);
							$itemEntity->flagForDespawn();
						}
					}elseif($itemItem->getId() === ItemIds::WOODEN_SWORD){
						if(!$arena->isMurderer($player)){
							$event->setCancelled();
						}
					}else{
						$event->setCancelled();
					}
				}
			}
		}
	}*/

	public function onItemDrop(PlayerDropItemEvent $event) : void{
		if($this->getPlugin()->getArenaByPlayer($event->getPlayer()) !== null){
			$event->setCancelled();
		}
	}

	public function onExhaust(PlayerExhaustEvent $event) : void{
		$player = $event->getPlayer();
		if($player instanceof Player and $this->plugin->findMurderPlayer($player) instanceof MurderPlayer
		){
			$event->setCancelled();
		}
	}

	/*public function onDamage(EntityDamageEvent $event) : void{
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
							}else{
								$arena->spawnCorpse($damaged);
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
	}*/

	public function onLevelChange(EntityLevelChangeEvent $event) : void{
		$player = $event->getEntity();
		$target = $event->getTarget();
		if($player instanceof Player){
			$murderPlayer = $this->plugin->findMurderPlayer($player);
			if($murderPlayer instanceof MurderPlayer){
				if($target !== $murderPlayer->getMurderArena()->getWorld() and $target !== $murderPlayer->getMurderArena()->getWaitingLobby()){
					$murderPlayer->onLeave();
				}
			}else{
				$arena = $this->plugin->getArenaByName($target->getFolderName());
				if($arena !== null){
					MurderPlayer::createSpectator($player, $arena, false);
				}
			}
		}
	}

	public function getPlugin() : MurderMain{
		return $this->plugin;
	}
}