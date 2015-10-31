<?php

namespace SkyBlock;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\plugin\PluginBase as Base;
use pocketmine\math\Vector3;
use pocketmine\block\Dirt;
use pocketmine\block\Sand;
use pocketmine\block\Grass;
use pocketmine\block\Wood;
use pocketmine\level\generator\object\Tree;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\block\Sapling;
use pocketmine\utils\Random;

class Main extends Base implements Listener{
	public function onEnable(){
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!(is_dir($this->getDataFolder()."Players/"))){
			@mkdir($this->getDataFolder()."Players/");
		}
		if(!(is_dir($this->getDataFolder()."Islands/"))){
			@mkdir($this->getDataFolder()."Islands/");
		}
		$this->getLogger()->info(TextFormat::GREEN . "Done!");
	}
	public function onDisable(){
		$this->getLogger()->info(TextFormat::RED . "SkyBlock disabled");
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if(strtolower($command->getName()) == "is"){
			if(!(isset($args[0]))){
				$sender->sendMessage(TextFormat::YELLOW . "You didn't add a subcommand");
				$sender->sendMessage(TextFormat::GREEN . "Use: " . TextFormat::RESET . "/is help");
				return true;
			}elseif(isset($args[0])){
				if($args[0] == "help"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.help")){
						if(!(isset($args[1])) or $args[1] == "1"){
							$sender->sendMessage(TextFormat::GREEN . "Showing help page 1 of 1");
							$sender->sendMessage(TextFormat::GREEN . "/is help");
							$sender->sendMessage(TextFormat::GREEN . "/is create");
							$sender->sendMessage(TextFormat::GREEN . "/is home");
							$sender->sendMessage(TextFormat::GREEN . "/is sethome");
							$sender->sendMessage(TextFormat::GREEN . "/is find (op only)");
							return true;
						}elseif($args[1] == "2"){
							$sender->sendMessage("More commands coming soon");
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "You cannot view the help menu");
						return true;
					}
				}elseif($args[0] == "create"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.create")){
						$senderIs = $this->getDataFolder()."Islands/".$sender->getName().".txt";
						if($sender->getLevel()->getName() == $this->getConfig()->get("Lobby")){
							$sender->sendMessage(TextFormat::YELLOW."You can't make an island in spawn, silly");
							return true;
							
						}else{
							if(!(file_exists($senderIs))){
								$this->makeIsland($sender->getName());
								return true;
							}else{
								$sender->sendMessage(TextFormat::YELLOW . "You already have an island");
								return true;
							}
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "You cannot create an island");
						return true;
					}
				}elseif($args[0] == "home"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.home")){
						if(!(file_exists($this->getDataFolder()."Islands/".$sender->getName().".txt"))){
							$sender->sendMessage("You don't have an island. Use /is create to make one");
							return true;
						}else{
							$level = $this->getServer()->getLevelByName(yaml_parse_file($this->getDataFolder()."Players/".$sender->getName().".txt"));
							if($level !== null){
								$sender->sendMessage(TextFormat::GREEN."Teleporting to your island...");
								if($sender->getLevel()->getName() !== $level->getName()){
									$sender->sendMessage("You are not in the same world as your island. Use ".TextFormat::YELLOW."/mw tp ".$level->getName().TextFormat::RESET." and try again");
									return true;
								}else{
									$sender->teleport(new Vector3(yaml_parse_file($this->getDataFolder()."Islands/".$sender->getName().".txt")));
									$sender->sendMessage(TextFormat::GREEN."Done!");
									return true;
								}
							}else{
								$sender->sendMessage("An error has occored.");
								return true;
							}
						}
					}else{
						$sender->sendMessage("You do not have permission to do that");
						return true;
					}
				}elseif($args[0] == "find"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.find")){
						if(isset($args[1])){
							$p = $sender->getServer()->getPlayer($args[1]);
							if($p instanceof Player){
								$name = $p->getName();
								if(file_exists($this->getDataFolder()."Islands/".$name.".txt")){
									$sender->sendMessage("The coords for ".$name."'s island are");
									$sender->sendMessage(file_get_contents($this->getDataFolder()."Islands/".$name.".txt"));
									$sender->sendMessage(file_get_contents($this->getDataFolder()."Players/".$name.".txt"));
									return true;
								}else{
									$sender->sendMessage($name . " does not have an island");
									return true;
								}
							}elseif(file_exists($this->getDataFolder()."Islands/".$args[1].".txt")){
								$sender->sendMessage("The coords for ".$args[1]."'s island are");
								$sender->sendMessage(file_get_contents($this->getDataFolder()."Islands/".$args[1].".txt"));
								$sender->sendMessage("in world ". file_get_contents($this->getDataFolder()."Players/".$args[1].".txt"));
								return true;
							}
						}else{
							$sender->sendMessage(TextFormat::YELLOW . "You need to specify a player");
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::YELLOW . "You cannot find the coords of a player's island");
						return true;
					}
				}elseif($args[0] == "delete"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.delete")){
						if(!(isset($args[1]))){
							$sender->sendMessage("Are you sure? Use /is delete yes to confirm");
							return true;
						}elseif($args[1] == "yes"){
								if(file_exists($this->getDataFolder()."Islands/".$sender->getName().".txt")){
									unlink($this->getDataFolder()."Islands/".$sender->getName().".txt");
									$sender->sendMessage("Your island has been deleted");
									return true;
								}else{
									$sender->sendMessage("You don't have an island");
									return true;
								}
							}elseif($args[1] == "no"){
								$sender->sendMessage("Okay, we won't delete your island");
								return true;
							}else{
								return false;
							}
						}else{
							$sender->sendMessage("You cannot delete your island");
							return true;
						}
					}elseif($args[0] == "sethome"){
						if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.sethome")){
							if(!(isset($args[1]))){
								$sender->sendMessage("Are you sure? Make sure you are on your island");
								$sender->sendMessage("Your island will be lost if you're not on your island. Do /is sethome yes to confirm");
								return true;
							}elseif($args[1] == "yes"){
								if(file_exists($this->getDataFolder()."Islands/".$sender->getName().".txt")){
									$sender->sendMessage("Setting your home...");
									$file = $this->getDataFolder()."Islands/".$sender->getName().".txt";
									unlink($file);
									$newFile = fopen($file, "w");
									fwrite($newFile, $sender->x.", ".$sender->y.", ".$sender->z);;
									$sender->sendMessage("Set your home.");
									return true;
								}else{
									$sender->sendMessage("You don't have an island");
									return true;
								}
							}elseif($args[1] == "no"){
								$sender->sendMessage("Okay, we won't set your home");
								return true;
							}else{
								$sender->sendMessage("Unknown subcommand: ".$args[1]);
								$sender->sendMessage("/sethome <yes | no>");
								return true;
							}
						}else{
							$sender->sendMessage("You don't have permission to set your home");
							return true;
						}
					}
				}
			}
		}
	
	public function makeIsland($name){
		$player = $this->getServer()->getPlayer($name);
		if(!($player instanceof Player)){
			return "Error: Player not found";
		}else{
			
			$randX = rand(30, 1000);
			$randZ = rand(30, 1000);
			$randY = rand(15, 100);
			
			$levelName = $this->getServer()->getPlayer($name)->getLevel();
			
			// Make a file for the island
			$islandFile = fopen($this->getDataFolder()."Islands/".$name.".txt", "w");
			fwrite($islandFile, $randX.", ".$randY.", ".$randZ);
			$playerFile = fopen($this->getDataFolder()."Players/".$name.".txt", "w");
			fwrite($playerFile, $player->getLevel()->getName());
			
			// Top layer of the island
			
			// 1st side
			$levelName->setBlock(new Vector3($randX, $randY, $randZ), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $randY, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $randY, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $randY, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $randY, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $randY, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $randY, $randZ+1), new Grass());
			
			// 2nd side
			$levelName->setBlock(new Vector3($randX+5, $randY, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $randY, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $randY, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $randY, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $randY, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $randY, $randZ+1), new Grass());
			
			// 3rd side 
			$levelName->setBlock(new Vector3($randX+4, $randY, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $randY, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $randY, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $randY, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $randY, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $randY, $randZ+1), new Grass());
			
			// 4th side
			$levelName->setBlock(new Vector3($randX+3, $randY, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $randY, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $randY, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $randY, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $randY, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $randY, $randZ+1), new Grass());
			
			// 5th side
			$levelName->setBlock(new Vector3($randX+2, $randY, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $randY, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $randY, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $randY, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $randY, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $randY, $randZ+1), new Grass());
			
			// 6th side
			$levelName->setBlock(new Vector3($randX+1, $randY, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $randY, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $randY, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $randY, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $randY, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $ranxY, $randZ+1), new Grass());
			
			// Middle layer of the island
			
			// 1st side
			$levelName->setBlock(new Vector3($randX, $randY-1, $randZ), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $randY-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $randY-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $randY-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $randY-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $randY-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $randY-1, $randZ+1), new Sand());
			
			// 2nd side
			$levelName->setBlock(new Vector3($randX+5, $randY-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $randY-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $randY-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $randY-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $randY-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $randY-1, $randZ+1), new Sand());
			
			// 3rd side
			$levelName->setBlock(new Vector3($randX+4, $randY-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $randY-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $randY-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $randY-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $randY-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $randY-1, $randZ+1), new Sand());
			
			// 4th side
			$levelName->setBlock(new Vector3($randX+3, $randY-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $randY-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $randY-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $randY-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $randY-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $randY-1, $randZ+1), new Sand());
			
			// 5th side
			$levelName->setBlock(new Vector3($randX+2, $randY-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $randY-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $randY-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $randY-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $randY-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $randY-1, $randZ+1), new Sand());
			
			// 6th side
			$levelName->setBlock(new Vector3($randX+1, $randY-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $randY-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $randY-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $randY-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $randY-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $randY-1, $randZ+1), new Sand());
			
			
			
			// Bottom layer of the island
			
			// 1st side
			$levelName->setBlock(new Vector3($randX, $randY-2, $randZ), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $randY-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $randY-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $randY-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $randY-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $randY-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $randY-2, $randZ+1), new Dirt());
			
			// 2nd side
			$levelName->setBlock(new Vector3($randX+5, $randY-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $randY-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $randY-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $randY-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $randY-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $randY-2, $randZ+1), new Dirt());
			
			// 3rd side
			$levelName->setBlock(new Vector3($randX+4, $randY-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $randY-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $randY-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $randY-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $randY-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $randY-2, $randZ+1), new Dirt());
			
			// 4th side
			$levelName->setBlock(new Vector3($randX+3, $randY-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $randY-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $randY-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $randY-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $randY-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $randY-2, $randZ+1), new Dirt());
			
			// 5th side
			$levelName->setBlock(new Vector3($randX+2, $randY-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $randY-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $randY-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $randY-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $randY-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $randY-2, $randZ+1), new Dirt());
			
			// 6th side
			$levelName->setBlock(new Vector3($randX+1, $randY-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $randY-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $randY-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $randY-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $randY-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $randY-2, $randZ+1), new Dirt());
			
			// Tree
			$type = 0;
			Tree::growTree($levelName, $randX+6, $randY+1, $randZ+6, new Random(mt_rand()), Sapling::OAK);
			
			// Teleport the player to their new island
			$player->teleport(new Position($randX, $randY+5, $randZ, $this->getServer()->getLevelByName($levelName)));
			$player->sendMessage(TextFormat::GREEN . "Welcome to your new island");
			$player->sendMessage(TextFormat::GREEN . "If your island didn't spawn,");
			$player->sendMessage(TextFormat::GREEN . "Use /is delete");
			$player->sendMessage(TextFormat::GREEN . "Then make a new island");
			
			// Give the player a starter kit
			
			// String
			$player->getInventory()->addItem(Item::get(287));
			$player->getInventory()->addItem(Item::get(287));
			$player->getInventory()->addItem(Item::get(287));
			$player->getInventory()->addItem(Item::get(287));
			$player->getInventory()->addItem(Item::get(287));
			
			// Emerald
			$player->getInventory()->addItem(Item::get(388));
			$player->getInventory()->addItem(Item::get(388));
			$player->getInventory()->addItem(Item::get(388));
			$player->getInventory()->addItem(Item::get(388));
			$player->getInventory()->addItem(Item::get(388));
			
			// Sapling
			$player->getInventory()->addItem(Item::get(6));
			$player->getInventory()->addItem(Item::get(6));
			$player->getInventory()->addItem(Item::get(6));
			$player->getInventory()->addItem(Item::get(6));
			$player->getInventory()->addItem(Item::get(6));
			
			// Water
			$player->getInventory()->addItem(Item::get(8));
			$player->getInventory()->addItem(Item::get(8));
			
			// Lava
			$player->getInventory()->addItem(Item::get(10));
			
			// Seeds
			$player->getInventory()->addItem(Item::get(295));
			$player->getInventory()->addItem(Item::get(295));
			$player->getInventory()->addItem(Item::get(295));
			$player->getInventory()->addItem(Item::get(295));
			$player->getInventory()->addItem(Item::get(295));
			
			// Melon seeds
			$player->getInventory()->addItem(Item::get(362));
			
			// Cactus
			$player->getInventory()->addItem(Item::get(81));
			
			// Iron
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			$player->getInventory()->addItem(Item::get(265));
			
			// Chest
			$player->getInventory()->addItem(Item::get(54));
			
			$this->getLogger()->info($name . TextFormat::YELLOW . " made an island");
		}
	}
	
	// When a player joins
	public function onPlayerJoinEvent(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(file_exists($this->getDataFolder()."Players/".$player->getName().".txt")){
			$player->sendMessage("Welcome back, " . $player->getName());
		}else{
			$this->getServer()->broadcastMessage(TextFormat::GREEN . "Welcome ".TextFormat::RESET.$player->getName().TextFormat::GREEN." to the server");
			$file = fopen($this->getDataFolder()."Players/".$player->getName().".txt", "w");
			fclose($file);
		}
	}
}
