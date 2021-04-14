<?php

namespace BossBar;

use pocketmine\plugin\Plugin;
use pocketmine\event\HandlerList;
use pocketmine\scheduler\PluginTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\Task;
use pocketmine\{Player, Server};
use BossBar\Main;
use BossBar\API;

class SendTask extends PluginTask{

    private $plugin;

	public function __construct(Plugin $plugin){
		parent::__construct($plugin);
		$this->plugin = $plugin;
		$this->server = $plugin->getServer();
	}
	
	public function getPlugin() : Plugin {
			return $this->plugin;
		}
	
		public function getServer(){
			return $this->server;
		}

	public function onRun(int $currentTick){
		$this->plugin->sendBossBar();
	}

	public function cancel(){
		$this->getHandler()->cancel();
	}
}