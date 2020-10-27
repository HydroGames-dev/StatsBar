<?php

namespace BossBar;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\Player;
use pocketmine\Server;

class API{

	const ENTITY = 37;

	public static function addBossBar($players, string $title, $ticks = null){
		if (empty($players)) return null;
		$eid = Entity::$entityCount++;
		$packet = new AddEntityPacket();
		$packet->entityRuntimeId = $eid;
		$packet->type = self::ENTITY;
		$packet->metadata = [Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1], Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 0 ^ 1 << Entity::DATA_FLAG_SILENT ^ 1 << Entity::DATA_FLAG_INVISIBLE ^ 1 << Entity::DATA_FLAG_NO_AI], Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title], Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 0], Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT, 0]];
		foreach ($players as $player){
			$pk = clone $packet;
			$pk->position = $player->getPosition()->asVector3()->subtract(0, 28);
			$player->dataPacket($pk);
		}
		$bpk = new BossEventPacket();
		$bpk->bossEid = $eid;
		$bpk->eventType = BossEventPacket::TYPE_SHOW;
		$bpk->title = $title;
		$bpk->healthPercent = 1;
		$bpk->unknownShort = 0;
		$bpk->color = 0;
		$bpk->overlay = 0;
		$bpk->playerEid = 0;
		Server::getInstance()->broadcastPacket($players, $bpk);
		return $eid;
	}
	public static function sendBossBarToPlayer(Player $player, int $eid, string $title, $ticks = null){
		self::removeBossBar([$player], $eid);
		$packet = new AddEntityPacket();
		$packet->entityRuntimeId = $eid;
		$packet->type = self::ENTITY;
		$packet->position = $player->getPosition()->asVector3()->subtract(0, 28);
		$packet->metadata = [Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1], Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 0 ^ 1 << Entity::DATA_FLAG_SILENT ^ 1 << Entity::DATA_FLAG_INVISIBLE ^ 1 << Entity::DATA_FLAG_NO_AI], Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title], Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 0], Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT, 0]];
		$player->dataPacket($packet);
		$bpk = new BossEventPacket();
		$bpk->bossEid = $eid;
		$bpk->eventType = BossEventPacket::TYPE_SHOW;
		$bpk->title = $title;
		$bpk->healthPercent = 1;
		$bpk->unknownShort = 0;
		$bpk->color = 0;
		$bpk->overlay = 0;
		$bpk->playerEid = 0;
		$player->dataPacket($bpk);
	}

	public static function setPercentage(int $percentage, int $eid, $players = []){
		if (empty($players)) $players = Server::getInstance()->getOnlinePlayers();
		if (!count($players) > 0) return;
		$upk = new UpdateAttributesPacket();
		$upk->entries[] = new BossBarValues(1, 600, max(1, min([$percentage, 100])) / 100 * 600, 'minecraft:health');
		$upk->entityRuntimeId = $eid;
		Server::getInstance()->broadcastPacket($players, $upk);
		$bpk = new BossEventPacket();
		$bpk->bossEid = $eid;
		$bpk->eventType = BossEventPacket::TYPE_SHOW;
		$bpk->title = "";
		$bpk->healthPercent = $percentage / 100;
		$bpk->unknownShort = 0;
		$bpk->color = 0;
		$bpk->overlay = 0;
		$bpk->playerEid = 0;
		Server::getInstance()->broadcastPacket($players, $bpk);
	}

	public static function setTitle(string $title, int $eid, $players = []){
		if (!count(Server::getInstance()->getOnlinePlayers()) > 0) return;
		$npk = new SetEntityDataPacket();
		$npk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title]];
		$npk->entityRuntimeId = $eid;
		Server::getInstance()->broadcastPacket($players, $npk);
		$bpk = new BossEventPacket();
		$bpk->bossEid = $eid;
		$bpk->eventType = BossEventPacket::TYPE_SHOW;
		$bpk->title = $title;
		$bpk->healthPercent = 1;
		$bpk->unknownShort = 0;
		$bpk->color = 0;
		$bpk->overlay = 0;
		$bpk->playerEid = 0;
		Server::getInstance()->broadcastPacket($players, $bpk);
	}

	public static function removeBossBar($players, int $eid){
		if (empty($players)) return false;
		$pk = new RemoveEntityPacket();
		$pk->entityUniqueId = $eid;
		Server::getInstance()->broadcastPacket($players, $pk);
		return true;
	}
}