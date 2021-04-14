<?php

namespace BossBar;

use BossBar\API;
use BossBar\SendTask;
use BossBar\BossBarValues;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use pocketmine\{Player, Server};
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\level\Level;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\HandlerList;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\Task;
use onebone\economyapi\EconomyAPI;
use _64FF00\PurePerms\PurePerms;
use JackMD\KDR\KDR;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener{

    private static $instance = null;

    private $purePerms;

    private $economyAPI;

    private $kdr;

    public $entityRuntimeId = null, $headBar = '', $cmessages = [], $changeSpeed = 0, $i = 0;

    public $API;

    public function onEnable()
    {
        $this->saveResource("config.yml");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->headBar = $this->getConfig()->get('head-message', '');
        $this->cmessages = $this->getConfig()->get('changing-messages', []);
        $this->changeSpeed = $this->getConfig()->get('change-speed', 0);
        if($this->changeSpeed > 0){
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new SendTask($this), 20 * $this->changeSpeed);
        }
        $this->economyAPI = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $this->purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        $this->kdr = $this->getServer()->getPluginManager()->getPlugin("KDR");
    }

    public static function getInstance(){
        return self::$instance;
    }

    public function onLoad(){
        self::$instance = $this;
    }

    public function onJoin(PlayerJoinEvent $ev){
        if(in_array($ev->getPlayer()->getLevel(), $this->getWorlds())){
            if($this->entityRuntimeId === null){
                $this->entityRuntimeId = API::addBossBar([$ev->getPlayer()], 'Please wait loading StatsBar...');
                $this->getServer()->getLogger()->debug($this->entityRuntimeId === NULL ? 'Couldn\'t add StatsBar' : 'Successfully added StatsBar with EID: ' . $this->entityRuntimeId);
            }else{
                API::sendBossBarToPlayer($ev->getPlayer(), $this->entityRuntimeId, $this->getText($ev->getPlayer()));
                $this->getServer()->getLogger()->debug('StatsBar EID exists already EID: ' . $this->entityRuntimeId);
            }
        }
    }

    public function onLevelChange(EntityLevelChangeEvent $ev){
		if ($ev->isCancelled() || !$ev->getEntity() instanceof Player) return;
		if (in_array($ev->getTarget(), $this->getWorlds())){
			if ($this->entityRuntimeId === null){
				$this->entityRuntimeId = API::addBossBar([$ev->getEntity()], 'Please wait loading StatsBar...');
				$this->getServer()->getLogger()->debug($this->entityRuntimeId === NULL ? 'Couldn\'t add StatsBar' : 'Successfully added StatsBar with EID: ' . $this->entityRuntimeId);
			} else{
				API::sendBossBarToPlayer($ev->getPlayer(), $this->entityRuntimeId, $this->getText($ev->getPlayer()));
				$this->getServer()->getLogger()->debug('StatsBar EID exists already EID: ' . $this->entityRuntimeId);
			}
		} else{
			API::removeBossBar([$ev->getEntity()], $this->entityRuntimeId);
		}
	}

	public function sendBossBar(){
		if ($this->entityRuntimeId === null) return;
		$this->i++;
		$worlds = $this->getWorlds();
		foreach ($worlds as $world){
			foreach ($world->getPlayers() as $player){
				API::setTitle($this->getText($player), $this->entityRuntimeId, [$player]);
			}
		}
	}

	public function getText(Player $player){
		$text = '';
		
		if (!empty($this->headBar)) $text .= $this->formatText($player, $this->headBar) . "\n" . "\n" . TextFormat::RESET;
		$currentMSG = $this->cmessages[$this->i % count($this->cmessages)];
		if (strpos($currentMSG, '%') > -1){
			$percentage = substr($currentMSG, 1, strpos($currentMSG, '%') - 1);
			if (is_numeric($percentage)) API::setPercentage(intval($percentage) + 0.5, $this->entityRuntimeId);
			$currentMSG = substr($currentMSG, strpos($currentMSG, '%') + 2);
		}
		$text .= $this->formatText($player, $currentMSG);
		return mb_convert_encoding($text, 'UTF-8');
	}

	public function formatText(Player $player, string $text){
		$text = str_replace("{display_name}", $player->getDisplayName(), $text);
		$text = str_replace("{name}", $player->getName(), $text);
		$text = str_replace("{x}", $player->getFloorX(), $text);
		$text = str_replace("{y}", $player->getFloorY(), $text);
		$text = str_replace("{z}", $player->getFloorZ(), $text);
		$text = str_replace("{ip}", $player->getAddress(), $text);
		$text = str_replace("{ping}", $player->getPing(), $text);
		$text = str_replace("{world}", (($levelname = $player->getLevel()->getName()) === false ? "" : $levelname), $text);
		$text = str_replace("{world_players}", count($player->getLevel()->getPlayers()), $text);
		$text = str_replace("{players}", count($player->getServer()->getOnlinePlayers()), $text);
		$text = str_replace("{max_online}", $player->getServer()->getMaxPlayers(), $text);
		$text = str_replace("{hour}", date('H'), $text);
		$text = str_replace("{minute}", date('i'), $text);
		$text = str_replace("{second}", date('s'), $text);
		$text = str_replace("{money}", $this->economyAPI->myMoney($player), $text);
		$text = str_replace("{rank}", $this->getPlayerRank($player), $text);
		$text = str_replace("{prefix}", $this->getPrefix($player), $text);
		$text = str_replace("{suffix}", $this->getSuffix($player), $text);
		$text = str_replace("{kdr}", $this->kdr->getProvider()->getKillToDeathRatio($player), $text);
		$text = str_replace("{deaths}", $this->kdr->getProvider()->getPlayerDeathPoints($player), $text);
		$text = str_replace("{kills}", $this->kdr->getProvider()->getPlayerKillPoints($player), $text);
		// Color Code Tags
		$text = str_replace("{EOL}", "\n", $text);
		$text = str_replace("{BLACK}", "&0", $text);
		$text = str_replace("{DARK_BLUE}", "&1", $text);
		$text = str_replace("{DARK_GREEN}", "&2", $text);
		$text = str_replace("{DARK_AQUA}", "&3", $text);
		$text = str_replace("{DARK_RED}", "&4", $text);
		$text = str_replace("{DARK_PURPLE}", "&5", $text);
		$text = str_replace("{GOLD}", "&6", $text);
		$text = str_replace("{GRAY}", "&7", $text);
		$text = str_replace("{DARK_GRAY}", "&8", $text);
		$text = str_replace("{BLUE}", "&9", $text);
		$text = str_replace("{GREEN}", "&a", $text);
		$text = str_replace("{AQUA}", "&b", $text);
		$text = str_replace("{RED}", "&c", $text);
		$text = str_replace("{LIGHT_PURPLE}", "&d", $text);
		$text = str_replace("{YELLOW}", "&e", $text);
		$text = str_replace("{WHITE}", "&f", $text);
		$text = str_replace("{OBFUSCATED}", "&k", $text);
		$text = str_replace("{BOLD}", "&l", $text);
		$text = str_replace("{STRIKETHROUGH}", "&m", $text);
		$text = str_replace("{UNDERLINE}", "&u", $text);
		$text = str_replace("{ITALIC}", "&o", $text);
		$text = str_replace("{RESET}", "&r", $text);
		// Color Codes Symbol
		$text = str_replace("&n", TextFormat::EOL, $text);
		$text = str_replace("&0", TextFormat::BLACK, $text);
		$text = str_replace("&1", TextFormat::DARK_BLUE, $text);
		$text = str_replace("&2", TextFormat::DARK_GREEN, $text);
		$text = str_replace("&3", TextFormat::DARK_AQUA, $text);
		$text = str_replace("&4", TextFormat::DARK_RED, $text);
		$text = str_replace("&5", TextFormat::DARK_PURPLE, $text);
		$text = str_replace("&6", TextFormat::GOLD, $text);
		$text = str_replace("&7", TextFormat::GRAY, $text);
		$text = str_replace("&8", TextFormat::DARK_GRAY, $text);
		$text = str_replace("&9", TextFormat::BLUE, $text);
		$text = str_replace("&a", TextFormat::GREEN, $text);
		$text = str_replace("&b", TextFormat::AQUA, $text);
		$text = str_replace("&c", TextFormat::RED, $text);
		$text = str_replace("&d", TextFormat::LIGHT_PURPLE, $text);
		$text = str_replace("&e", TextFormat::YELLOW, $text);
		$text = str_replace("&f", TextFormat::WHITE, $text);
		$text = str_replace("&k", TextFormat::OBFUSCATED, $text);
		$text = str_replace("&l", TextFormat::BOLD, $text);
		$text = str_replace("&m", TextFormat::STRIKETHROUGH, $text);
		$text = str_replace("&u", TextFormat::UNDERLINE, $text);
		$text = str_replace("&o", TextFormat::ITALIC, $text);
		$text = str_replace("&r", TextFormat::RESET, $text);

		return $text;
	}

	private function getWorlds(){
		$mode = $this->getConfig()->get("mode", 0);
		$worldnames = $this->getConfig()->get("worlds", []);
		$worlds = [];
		
		switch ($mode){
			case 0:
				$worlds = $this->getServer()->getLevels();
				break;
			case 1:
				foreach ($worldnames as $name){
					if (!is_null($level = $this->getServer()->getLevelByName($name))) $worlds[] = $level;
					else $this->getLogger()->warning("Config error! World " . $name . " not found!");
				}
				break;
			case 2:
				$worlds = $this->getServer()->getLevels();
				foreach ($worlds as $world){
					if (!in_array(strtolower($world->getName()), $worldnames)){
						$worlds[] = $world;
					}
				}
				break;
		}
		return $worlds;
	}
	
	public function getPlayerRank(Player $player): string{
			$group = $this->purePerms->getUserDataMgr()->getData($player)['group'];

			if($group !== null){
				return $group;
			}else{
				return "No Rank";
			}
		}

		public function getPrefix(Player $player, $levelName = null): string{
			$purePerms = $this->purePerms;
			$prefix = $purePerms->getUserDataMgr()->getNode($player, "prefix");

			if($levelName === null){
				if(($prefix === null) || ($prefix === "")){
					return "No Prefix";
				}

				return (string) $prefix;
			}else{
				$worldData = $purePerms->getUserDataMgr()->getWorldData($player, $levelName);

				if(empty($worldData["prefix"]) || $worldData["prefix"] == null){
					return "No Prefix";
				}

				return $worldData["prefix"];
			}
		}

		public function getSuffix(Player $player, $levelName = null): string{
			$purePerms = $this->purePerms;
			$suffix = $purePerms->getUserDataMgr()->getNode($player, "suffix");

			if($levelName === null){
				if(($suffix === null) || ($suffix === "")){
					return "No Suffix";
				}

				return (string) $suffix;
			}else{
				$worldData = $purePerms->getUserDataMgr()->getWorldData($player, $levelName);

				if(empty($worldData["suffix"]) || $worldData["suffix"] == null){
					return "No Suffix";
				}

				return $worldData["suffix"];
			}
		}
}