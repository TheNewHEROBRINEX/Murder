namespace CastleGames\Murder;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;

class MurderListener implents Listener {

  private $plugin;
  
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
        if (isset($this->plugin->setspawns[$name][$world])) {
            $spawns = $this->getConfig()->get($world, []);
            $spawns[] = array($x, $y, $z);
            $this->plugin->getConfig()->set($world, $spawns);
            --$this->plugin->setspawns[$name][$world];
            $player->sendMessage("§eSpawn Murder del mondo $world settato a§f $x $y $z. " . (($this->plugin->setspawns[$name][$world] == 1) ? "§eRimane " : "§eRimangono ") . $this->setspawns[$name][$world] . " §espawn da settare");
            if ($this->plugin->setspawns[$name][$world] <= 0) {
                unset($this->plugin->setspawns[$name][$world]);
                $this->plugin->getConfig()->save();
            }
        }
    }
 }
