<?php

namespace ShaduuYT\GenBuckets;

use onebone\economyapi\EconomyAPI;
use pocketmine\block\Solid;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{
	/** @var Config */
	private $conf;

	/** @var array */
	private $buckets = [];

	/** @var EconomyAPI */
	private $eco;


	public function onLoad(){
		$this->getLogger()->info('Loading Configuration...');
		@mkdir($this->getDataFolder());
		$this->conf = new Config($this->getDataFolder() . "config.yml",Config::YAML,[
			"buckets" => [
				"cobble" => [
					"blockId" => 4,
					"bucketDamage" => 4,
					"bucketPrice" => 1000,
					"bucketName" => TextFormat::GOLD . "Cobble Bucket",
				],
				"sand" => [
					"blockId" => 12,
					"bucketDamage" => 12,
					"bucketPrice" => 10000,
					"bucketName" => TextFormat::GOLD . "Sand Bucket",
				],
				"obsidian" => [
					"blockId" => 49,
					"bucketDamage" => 49,
					"bucketPrice" => 150000,
					"bucketName" => TextFormat::GOLD . "Obsidian Bucket",
				],
				"bedrock" => [
					"blockId" => 7,
					"bucketDamage" => 7,
					"bucketPrice" => 1500000,
					"bucketName" => TextFormat::GOLD . "Bedrock Bucket",
				],
			]
		]);

		$this->buckets = $this->conf->get("buckets",[]);
	}

	public function onEnable(){

        $this->eco = EconomyAPI::getInstance();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
	
	public function onDisable(){
        $this->getLogger()->info('GenBuckets Disabled');
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($command->getName() === "gbuy"){
			if(isset($args[0])){
				if($sender instanceof Player){
					if(isset($this->buckets[strtolower($args[0])])){
						$arr = $this->buckets[strtolower($args[0])];
						$p = $sender->getName();
						if($this->eco->myMoney($p) >= $arr["bucketPrice"]){
							$this->eco->reduceMoney($p, $arr["bucketPrice"]);

							$bucket = Item::get(Item::BUCKET);
							$bucket->setDamage($arr["bucketDamage"]);
							$bucket->setCustomName($arr["bucketName"]);

							$sender->getInventory()->addItem($bucket);
							$sender->sendMessage(TextFormat::GREEN . "Purchased " . $arr["bucketName"] . TextFormat::RESET);
							return true;
						} else {
							$sender->sendMessage(TextFormat::RED . "Not enough money." . TextFormat::RESET);
							return true;
						}
					} else {
						$sender->sendMessage(TextFormat::RED . "Bucket does not exist. Use /gbuy to get a list of available GenBuckets." . TextFormat::RESET);
						return true;
					}
				}
			} else {
				$sender->sendMessage(TextFormat::GOLD . " ----------.[" . TextFormat::AQUA . "GenBuckets" . TextFormat::GOLD . "].---------- ");
				$i = 0;
				foreach($this->buckets as $bucket){
					$sender->sendMessage($bucket["bucketName"] . TextFormat::RESET . TextFormat::AQUA . " - " . TextFormat::RESET . TextFormat::GOLD . "$" . $bucket["bucketPrice"] . TextFormat::RESET);
					$i++;
				}
			}
		}
		return true;
	}

	/*public function onHeld(PlayerItemHeldEvent $event){
		$player = $event->getPlayer();
		$i = $player->getInventory()->getItemInHand();

		if($i->getId() === Item::BUCKET){
			foreach($this->buckets as $bucket){
				if($i->getdamage() === $bucket["bucketDamage"]){
					$player->sendPopup($bucket["bucketName"]); // #BlameMojang
					return true;
				}
			}
		}
	}*/

	/**
     * @priority MONITOR
     */
	public function onTap(PlayerInteractEvent $event) {
		if(!$event->isCancelled()){
			$player = $event->getPlayer();
			$b = $event->getBlock();
			$i = $player->getInventory()->getItemInHand();
			$face = $event->getFace();

			if($i->getId() === Item::BUCKET){
				foreach($this->buckets as $bucket){
					if($i->getdamage() === $bucket["bucketDamage"]){
						$event->setCancelled(true);
						$player->getInventory()->setItemInHand(Item::get(0));
						$x = $b->getX();
						$evY = $b->getY();
						$y = $evY;
						$z = $b->getZ();
						$evLEVEL = $event->getBlock()->getLevel();

						switch($face){
							case 2:
								$z--;
								break;
							case 3:
								$z++;
								break;

							case 4:
								$x--;
								break;
							case 5:
								$x++;
								break;

							case 1:
								$y++;
								break;
							case 0:
								$y--;
								break;
						}

						while($y > 1) {
							if(!($evLEVEL->getBlock(new Vector3($x, $y, $z)) instanceof Solid)){
								$evLEVEL->setBlock(new Vector3($x, $y, $z), Block::get($bucket["blockId"]), false, false);
								$y--;
								return true;
							} else {
								break;
							}
						}
					}
				}
			}
		}
	}
}
