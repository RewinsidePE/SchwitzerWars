<?php

declare(strict_types=1);

namespace Fludixx\SchwitzerWars;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDataSaveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Sign;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\sound\GhastShootSound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\scheduler\Task;
use pocketmine\tile\Tile;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as f;
use pocketmine\item\Item;

class Main extends PluginBase implements Listener {

	public $prefix = f::YELLOW."Push".f::GOLD."Wars ".f::DARK_GRAY."> ".f::WHITE;
	public $implemnted = ["8x1"];
	public $setup = null;
	public $arena = null;
	public $fjoin = false;

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info($this->prefix."PushWars wurde Aktiviert!");
		if(!is_dir("/cloud")) {@mkdir("/cloud");}
		if(!is_dir("/cloud/pw")) {@mkdir("/cloud/pw");}
		if(!is_dir("/cloud/users")) {@mkdir("/cloud/users");}
		$this->getLogger()->info("Regestrierte Arenas:");
		foreach(glob('/cloud/pw/*.yml') as $file) {
			$c = new Config("$file");
			$c->set("players", 0);
			$c->set("countdown", 60);
			$c->set("busy", false);
			$c->save();
			$this->getLogger()->info(" - ".$file);
		}

	}

	public function getColor(int $pos) {
		if($pos == 1) {return f::GOLD."Orange".f::WHITE;}
		elseif($pos == 2) {return f::LIGHT_PURPLE."Magenta".f::WHITE;}
		elseif($pos == 3) {return f::BLUE."Blau".f::WHITE;}
		elseif($pos == 4) {return f::YELLOW."Gelb".f::WHITE;}
		elseif($pos == 5) {return f::GREEN."Grün".f::WHITE;}
		elseif($pos == 6) {return f::LIGHT_PURPLE."Pink".f::WHITE;}
		elseif($pos == 7) {return f::DARK_GRAY."Grau".f::WHITE;}
		elseif($pos == 8) {return f::GRAY."Hell Grau".f::WHITE;}
		else {
			return "Wool:$pos";
		}
	}

	public function getEq(Player $player) {
		$inv = $player->getInventory();
		$inv->clearAll();
		$knock = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::KNOCKBACK), 1);
		$effy = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 1);
		$unbreak = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3);
		$sharp = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SHARPNESS), 1);
		$power = new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::POWER), 1);
		$stick = Item::get(Item::STICK, 0, 1);
		$stick->addEnchantment($knock);
		$stick->setCustomName(f::YELLOW."Knockback Stick");
		$sword = Item::get(Item::STONE_SWORD, 0, 1);
		$sword->setCustomName(f::YELLOW."Schwert");
		$sword->addEnchantment($unbreak);
		$sword->addEnchantment($sharp);
		$angel = Item::get(Item::FISHING_ROD, 0, 1);
		$angel->setCustomName(f::YELLOW."Angel");
		$bogen = Item::get(Item::BOW, 0, 1);
		$bogen->setCustomName(f::YELLOW."Bogen");
		$bogen->addEnchantment($unbreak);
		$bogen->addEnchantment($power);
		$gapple = Item::get(Item::GOLDEN_APPLE, 0, 2);
		$arrows = Item::get(Item::ARROW, 0, 10);
		$picke = Item::get(Item::STONE_PICKAXE, 0, 1);
		$picke->addEnchantment($effy);
		$picke->addEnchantment($unbreak);
		$picke->setCustomName(f::YELLOW."Spitzhacke");
		$webs = Item::get(Item::WEB, 0, 5);
		$webs->setCustomName(f::YELLOW."Cobwebs");
		$blocks = Item::get(Item::SANDSTONE, 0, 64);
		$blocks->setCustomName(f::YELLOW."Sandsteine");
		$inv->setItem(0, $stick);
		$inv->setItem(1, $sword);
		$inv->setItem(2, $picke);
		$inv->setItem(3, $angel);
		$inv->setItem(4, $webs);
		$inv->setItem(5, $bogen);
		$inv->setItem(6, $blocks);
		$inv->setItem(7, $blocks);
		$inv->setItem(8, $gapple);
		$inv->setItem(9, $arrows);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($command->getName() == "pw") {
			if($args['0'] == "start") {
				if ($sender->hasPermission("pw.start")) {
					if ($sender instanceof Player) {
						$levelname = $sender->getLevel()->getFolderName();
						$c = new Config("/cloud/pw/$levelname.yml");
						$c->set("countdown", 10);
						$c->save();
						$sender->sendMessage($this->prefix . "Countdownwert wurde auf 10 gestellt.");
					} else {
						$sender->sendMessage($this->prefix . "Uhh.. Du bist kein Spieler?");
					}
				} else {
					$sender->sendMessage($this->prefix . "Uhh.. Komm wieder wenn du die Rechte hast.");
				}
			}
			elseif(!empty($args['0']) && empty($args['1'])) {
				} else {
					$sender->sendMessage($this->prefix . "Uhh.. /pw [ARENA] [8x1...]");
				}
			if (!$sender->isOp()) {
				$sender->sendMessage($this->prefix . "Uhh.. Komm wieder wenn du OP bist.");
				return false;
			} else {
				if (empty($args['0']) || empty($args['1'])) {
					$sender->sendMessage($this->prefix . "Uhh.. /pw [ARENA] [8x1...]");
					return false;
				} else {
					$mode = null;
					foreach ($this->implemnted as $game) {
						if ($game == $args[1]) {
							$mode = $args['1'];
						}
					}
					if ($mode == null) {
						$sender->sendMessage($this->prefix . "Uhh.. Die Dimension " . $args['1'] . " ist noch net Implementiert.");
						return false;
					} else {
						$sender->sendMessage($this->prefix . "OK. " . $args['1'] . " wurde als Deminsion ausgewählt.");
						$this->getServer()->loadLevel((string)$args['0']);
						$arena = $this->getServer()->getLevelByName((string)$args['0']);
						if (!$arena) {
							$sender->sendMessage($this->prefix . "Uhh.. Keine Arena namens " . $args['0'] . " gefunden.");
							return false;
						} else {
							$pos = new Position($arena->getSafeSpawn()->getX(), $arena->getSafeSpawn()->getY(),
								$arena->getSafeSpawn()->getZ(), $arena);
							if ($sender instanceof Player) {
								$sender->teleport($pos);
								$sender->sendMessage($this->prefix."Plaziere einen Block auf den Spawn vom 1 Spieler!");
								$inv = $sender->getInventory();
								$wolle = Item::get(Item::WOOL, 1, 1);
								$inv->setItem(0, $wolle);
								if($mode = "8x1") {
									$this->setup = "8x1-1";
								}
								return true;
							} else {
								$sender->sendMessage($this->prefix . "Uhh.. Du bist kein Spieler?");
								return false;
							}
						}
					}
				}
			}
		}
		if($command->getName() == "pwsign") {
			if(empty($args['0'])) {
				$sender->sendMessage($this->prefix."Uhh.. /pwsign [ARENA]");
				return false;
			} else {
				$arena = $args['0'];
				if(is_file("/cloud/pw/$arena.yml")) {
					$c = new Config("/cloud/pw/$arena.yml");
					$sender->sendMessage($this->prefix."OK. $arena wurde gefunden. Bitte klicke auf ein Schild.");
					$this->setup = "sign-1";
					$this->arena = $arena;
					return true;
				} else {
					$sender->sendMessage($this->prefix."Uhh.. So eine Arena wurde nie regestriert.");
					return false;
				}
			}
		}
	}

	public function onPlace(BlockPlaceEvent $event) {
		$player = $event->getPlayer();
		$name = $player->getName();
		$c = new Config("/cloud/users/$name.yml", Config::YAML);
		$pos = $c->get("pos");
		if(($this->setup == null || $this->setup == "sign-1") && $player->isOp() && $pos != false) {
			return true;
		} else {
			if ($player->isOp() && $this->setup != null) {
				if ($this->setup == "8x1-1") {
					$event->setCancelled(true);
					$posarray = [$event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ()];
					$cname = $player->getLevel()->getFolderName();
					$c = new Config("/cloud/pw/$cname.yml", Config::YAML);
					$c->set("dimension", "8*1");
					$c->set("p1", $posarray);
					$c->save();
					$player->sendMessage($this->prefix . "OK. Jetzt bitte den 2.");
					$inv = $player->getInventory();
					$wolle = Item::get(Item::WOOL, 2, 1);
					$inv->setItem(0, $wolle);
					$this->setup = "8x1-2";
				} elseif ($this->setup == "8x1-2") {
					$event->setCancelled(true);
					$posarray = [$event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ()];
					$cname = $player->getLevel()->getFolderName();
					$c = new Config("/cloud/pw/$cname.yml", Config::YAML);
					$c->set("p2", $posarray);
					$c->save();
					$player->sendMessage($this->prefix . "OK. Jetzt bitte den 3.");
					$inv = $player->getInventory();
					$wolle = Item::get(Item::WOOL, 3, 1);
					$inv->setItem(0, $wolle);
					$this->setup = "8x1-3";
				} elseif ($this->setup == "8x1-3") {
					$event->setCancelled(true);
					$posarray = [$event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ()];
					$cname = $player->getLevel()->getFolderName();
					$c = new Config("/cloud/pw/$cname.yml", Config::YAML);
					$c->set("p3", $posarray);
					$c->save();
					$player->sendMessage($this->prefix . "OK. Jetzt bitte den 4.");
					$inv = $player->getInventory();
					$wolle = Item::get(Item::WOOL, 4, 1);
					$inv->setItem(0, $wolle);
					$this->setup = "8x1-4";
				} elseif ($this->setup == "8x1-4") {
					$event->setCancelled(true);
					$posarray = [$event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ()];
					$cname = $player->getLevel()->getFolderName();
					$c = new Config("/cloud/pw/$cname.yml", Config::YAML);
					$c->set("p4", $posarray);
					$c->save();
					$player->sendMessage($this->prefix . "OK. Jetzt bitte den 5.");
					$inv = $player->getInventory();
					$wolle = Item::get(Item::WOOL, 5, 1);
					$inv->setItem(0, $wolle);
					$this->setup = "8x1-5";
				} elseif ($this->setup == "8x1-5") {
					$event->setCancelled(true);
					$posarray = [$event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ()];
					$cname = $player->getLevel()->getFolderName();
					$c = new Config("/cloud/pw/$cname.yml", Config::YAML);
					$c->set("p5", $posarray);
					$c->save();
					$player->sendMessage($this->prefix . "OK. Jetzt bitte den 6.");
					$inv = $player->getInventory();
					$wolle = Item::get(Item::WOOL, 6, 1);
					$inv->setItem(0, $wolle);
					$this->setup = "8x1-6";
				} elseif ($this->setup == "8x1-6") {
					$event->setCancelled(true);
					$posarray = [$event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ()];
					$cname = $player->getLevel()->getFolderName();
					$c = new Config("/cloud/pw/$cname.yml", Config::YAML);
					$c->set("p6", $posarray);
					$c->save();
					$player->sendMessage($this->prefix . "OK. Jetzt bitte den 7.");
					$inv = $player->getInventory();
					$wolle = Item::get(Item::WOOL, 7, 1);
					$inv->setItem(0, $wolle);
					$this->setup = "8x1-7";
				} elseif ($this->setup == "8x1-7") {
					$event->setCancelled(true);
					$posarray = [$event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ()];
					$cname = $player->getLevel()->getFolderName();
					$c = new Config("/cloud/pw/$cname.yml", Config::YAML);
					$c->set("p7", $posarray);
					$c->save();
					$player->sendMessage($this->prefix . "OK. Jetzt bitte den 8.");
					$inv = $player->getInventory();
					$wolle = Item::get(Item::WOOL, 8, 1);
					$inv->setItem(0, $wolle);
					$this->setup = "8x1-8";
				} elseif ($this->setup == "8x1-8") {
					$event->setCancelled(true);
					$posarray = [$event->getBlock()->getX(), $event->getBlock()->getY(), $event->getBlock()->getZ()];
					$cname = $player->getLevel()->getFolderName();
					$c = new Config("/cloud/pw/$cname.yml", Config::YAML);
					$c->set("p8", $posarray);
					$c->save();
					$c->set("players", 0);
					$c->save();
					$player->sendMessage($this->prefix . "OK. Jetzt sind wir Fertig!");
					$inv = $player->getInventory();
					$wolle = Item::get(0, 0, 0);
					$inv->setItem(0, $wolle);
					$this->setup = null;
				}
			}
		}
	}

	public function onInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		$name = $player->getName();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		if($this->setup == "sign-1" && $player->isOp()) {
			if ($tile instanceof \pocketmine\tile\Sign) {
				$c = new Config("/cloud/pw/$this->arena.yml", Config::YAML);
				$dimension = $c->get("dimension");
				$c->set("players", 0);
				$c->save();
				$playeramout = 0;
				$playeramout = eval("return $dimension;");
				$dimension = str_replace("*", "x", $dimension);
				$tile->setText(
				f::YELLOW."Push".f::GOLD."Wars",
				f::DARK_GRAY."[".f::GREEN."$dimension".f::DARK_GRAY."]",
				f::YELLOW."0 ".f::DARK_GRAY."/ ".f::GREEN."$playeramout"
				.f::DARK_GRAY."]",
				"$this->arena"
				);
				$player->sendMessage($this->prefix."OK. Schild wurde erstellt.");
				$this->setup = null;
				return true;
		} else {
				$player->sendMessage($this->prefix . "Uhh.. Das ist kein Schild.");
				$this->setup = null;
				return false;
			}
		} else {
			if($tile instanceof \pocketmine\tile\Sign) {
				$text = $tile->getText();
				if($text['0'] == f::YELLOW."Push".f::GOLD."Wars") {
					$player->sendMessage($this->prefix."Du wirst Teleportiert...");
					$cp = new Config("/cloud/users/$name.yml", Config::YAML);
					$cp->set("pos", false);
					$cp->save();
					$this->getServer()->loadLevel((string)$text['3']);
					$cplayercount = (int)$text[2][3];
					$cplayercount = $cplayercount+1;
					$c = new Config("/cloud/pw/".$text[3].".yml", Config::YAML);
					$dimension = $c->get("dimension");
					$playeramout = eval("return $dimension;");
					if($cplayercount == $playeramout) {
						$tile->setLine(0, f::RED."PushWars");
						$player->sendMessage($this->prefix."Uhh.. Die Arena ist voll oder schon gestartet.");
					}
					$tile->setLine(2, f::YELLOW."$cplayercount ".f::DARK_GRAY."/ ".f::GREEN."$playeramout");
					$arena = $this->getServer()->getLevelByName((string)$text['3']);
					$arena->setAutoSave(false);
					$pos = $arena->getSafeSpawn()->asPosition();
					$player->setGamemode(0);
					$player->teleport($pos);
					$players = $this->getServer()->getOnlinePlayers();
					$counter = 0;
					$playerarray = array();
					foreach($players as $person) {
						$level = $person->getLevel()->getFolderName();
						if($level == $arena->getFolderName()) {
							$counter++;
							$playerarray[] = $person;
						}
					}
					if($counter == 2) {
						$this->getLogger()->info("Es sind 2 Spieler in der Arena ".$arena->getFolderName());
						foreach($playerarray as $person) {
							$person->sendMessage($this->prefix."Das spiel beginnt in 60 Sekunden!");
							$c = new Config("/cloud/pw/".$arena->getFolderName().".yml", Config::YAML);
							$c->set("countdown", 60);
							$c->save();
							$this->getScheduler()->scheduleRepeatingTask(new Countdown($this, $arena), 40);
						}
					}
					foreach($playerarray as $person) {
						$person->sendMessage($this->prefix.$player->getName()." joined the Game! ".f::DARK_GRAY."["
							.f::YELLOW."$counter".f::DARK_GRAY."]");
					}

				}
				elseif($text['0'] == f::RED."PushWars") {
					$player->sendMessage($this->prefix."Uhh.. Die Arena ist voll oder schon gestartet.");
				}
			}
		}
	}

	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$player->setGamemode(0);
		$pos = $this->getServer()->getDefaultLevel()->getSafeSpawn();
		$player->teleport($pos);
		$player->setSpawn($pos);
		if($this->fjoin == false) {
			$levelname = $this->getServer()->getDefaultLevel()->getFolderName();
			$this->getLogger()->info($this->prefix."Initialisiere SignUpdater auf $levelname...");
			$tiles = $this->getServer()->getDefaultLevel()->getTiles();
			foreach($tiles as $tile) {
				if($tile instanceof \pocketmine\tile\Sign) {
					$text = $tile->getText();
					if($text[0] == f::YELLOW."Push".f::GOLD."Wars" || $text[0] == f::RED."PushWars") {
						$this->getScheduler()->scheduleRepeatingTask(new SignUpdater($this, $tile), 20);
						$this->getLogger()->info("SignUpdater Task wurde gestartet!");
					}
				}
			}
			$this->fjoin = true;
		}
		$name = $player->getName();
		$cp = new Config("/cloud/users/$name.yml", Config::YAML);
		$cp->set("pos", false);
		$cp->save();
	}

	public function onBreak(BlockBreakEvent $event) {
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$c = new Config("/cloud/users/".$player->getName().".yml", Config::YAML);
		$pos = (int)$c->get("pos");
		$air = Item::get(0, 0, 0);
		$drops = array($air);
		$event->setDrops($drops);
		if($block->getId() == 35 && $pos != false) {
				if($pos == $block->getDamage()) {$player->sendMessage($this->prefix."Du kannst nicht deinen eigenen Block abbauen!");
				$event->setCancelled(true);return 0;}
				$player->getLevel()->addSound(new GhastShootSound(new Vector3($block->getX(), $block->getY(),
					$block->getZ())));
				$arenaname = $player->getLevel()->getFolderName();
				$players = $this->getServer()->getOnlinePlayers();
				foreach($players as $person) {
					if($person->getLevel()->getFolderName() == $arenaname) {
						$cp = new Config("/cloud/users/".$person->getName().".yml", Config::YAML);
						$cpos = (int)$cp->get("pos");
						if($cpos == (int)$block->getDamage()) {
							$cp->set("wool", false);
							$cp->save();
							$person->sendMessage($this->prefix."Dein Block wurde abgebaut!");
						}
						$person->sendMessage($this->prefix."Der Block von Team ".$this->getColor((int)$block->getDamage())." wurde zerstört!");
					}
				}
		}
		elseif($block->getId() == Item::SANDSTONE && $pos != false) {
			return true;
		}
		elseif($block->getId() == Item::WEB && $pos != false) {
			return true;
		}
		else {
			$event->setCancelled(true);
		}
	}

	public function onDeath(PlayerDeathEvent $event) {
		$player = $event->getPlayer();
		$name = $player->getName();
		$level  = $player->getLevel();
		$cp = new Config("/cloud/users/$name.yml", Config::YAML);
		$cp->set("lvl", $level->getFolderName());
		$cp->save();
		$levelname = $level->getFolderName();
		$players = $this->getServer()->getOnlinePlayers();
		foreach($players as $person) {
			if($person->getLevel()->getFolderName() == $levelname) {
				$person->sendMessage($this->prefix."$name died!");
				$event->setDrops(array(Item::get(Item::GOLDEN_APPLE, 0, 1)));
			}
		}
	}

	public function onRespawn(PlayerRespawnEvent $event) {
	$player = $event->getPlayer();
	$name = $player->getName();
	$cp = new Config("/cloud/users/$name.yml", Config::YAML);
	$pos = $cp->get("pos");
	$lvl = (string)$cp->get("lvl");
	$level = $this->getServer()->getLevelByName($lvl);
	if(is_file("/cloud/pw/$lvl.yml") && $pos != false) {
		$this->getLogger()->info("Level Found!");
		$c = new Config("/cloud/pw/$lvl.yml", Config::YAML);
		$cp = new Config("/cloud/users/$name.yml", Config::YAML);
		$wool = (bool)$cp->get("wool");
		if($wool == false) {
			$this->getLogger()->info("$lvl -> Wool dosen't exists! ($name)");
			$player->sendMessage($this->prefix.f::RED.f::BOLD."Du bist Ausgeschieden!");
			$level = $this->getServer()->getDefaultLevel();
			$pos = $level->getSafeSpawn();
			$player->teleport($pos);
			$player->setSpawn($pos);
			return 0;
		} else {
			$pos = $cp->get("pos");
			$spawn = (array)$c->get("p$pos");
			$this->getLogger()->info("$name -> p$pos");
			$this->getLogger()->info("X: ".$spawn['0']." Y: ".$spawn['1']." Z: ".$spawn['2']." Level: $lvl");
			$spos = new Position($spawn['0'], $spawn['1'], $spawn['2'], $level);
			$this->getEq($player);
			$player->teleport($spos);
		}
	}
	}

	public function onHunger(PlayerExhaustEvent $event) {
		$event->getPlayer()->setFood(20);
	}

	public function onDmg(EntityDamageByEntityEvent $event) {
		$player = $event->getEntity();
		if($player instanceof Player) {
			$name = $player->getName();
			$c = new Config("/cloud/users/$name.yml", Config::YAML);
			$pos = $c->get("pos");
			if($pos == false) {
				$event->setCancelled(true);
				return false;
			} else {
				return true;
			}
		}
	}

	public function onDisable() : void{
		$this->getLogger()->info("Ausgeschalten.");
	}
}

class Countdown extends Task
{
	public $plugin;
	public $level;

	public function __construct(Main $plugin, Level $level)
	{

		/**
		 * @param Main $plugin
		 * @param Level $level
		 */

		$this->plugin = $plugin;
		$this->level = $level;
	}

	public function onRun(int $tick)
	{
		$name = $this->level->getFolderName();
		$c = new Config("/cloud/pw/$name.yml", Config::YAML);
		$cd = (int)$c->get("countdown");
		$cd = $cd-1;
		$c->set("countdown", $cd);
		$c->save();
		$time = $c->get("countdown");
		$players = $this->plugin->getServer()->getOnlinePlayers();
		$counter = 0;
		foreach ($players as $player) {
			if ($player->getLevel()->getFolderName() == $name) {
				$counter++;
				$player->setXpLevel((int)$time);
				$xpbar = (double)bcmul((string)bcdiv((string)1, (string)60, 6), (string)$time, 6);
				$player->setXpProgress($xpbar);
			}
		}
		if($time == 30) {
			$players = $this->plugin->getServer()->getOnlinePlayers();
			foreach ($players as $player) {
				if ($player->getLevel()->getFolderName() == $name) {
					$player->sendMessage($this->plugin->prefix . "Noch 30 Sekunden!");
				}
			}
		}
		if($time == 10) {
			$players = $this->plugin->getServer()->getOnlinePlayers();
			foreach ($players as $player) {
				if ($player->getLevel()->getFolderName() == $name) {
					$player->sendMessage($this->plugin->prefix . "Noch 10 Sekunden!");
				}
			}
		}
		if($time == 5) {
			$players = $this->plugin->getServer()->getOnlinePlayers();
			foreach ($players as $player) {
				if ($player->getLevel()->getFolderName() == $name) {
					$player->sendMessage($this->plugin->prefix . "Noch 5 Sekunden!");
				}
			}
		}
		if ($counter == 1) {
			$players = $this->plugin->getServer()->getOnlinePlayers();
			foreach ($players as $player) {
				if ($player->getLevel()->getFolderName() == $name) {
					$player->sendMessage($this->plugin->prefix . "Countdown wurde unterbrochen! Zuwenige Spieler.");
					$this->plugin->getScheduler()->cancelTask($this->getTaskId());
				}
			}
		}
		if($time == 0) {
			$players = $this->plugin->getServer()->getOnlinePlayers();
			$teamint = 0;
			foreach ($players as $player) {
				if($player->getLevel()->getFolderName() == $name) {
					$player->sendMessage(f::BOLD.f::GREEN."Das Spiel beginnt!");
					// Gebe jedem Spieler ein zufälliges Team
						$teamint++;
						$player->setGamemode(0);
						$player->setXpLevel(0);
						$spawn = (array)$c->get("p$teamint");
						$pos = new Position($spawn['0'], $spawn['1'], $spawn['2'], $this->level);
						$pname = $player->getName();
						$cp = new Config("/cloud/users/$pname.yml", Config::YAML);
						$cp->set("pos", $teamint);
						$cp->set("wool", true);
						$cp->save();
						$player->teleport($pos);
						$player->setSpawn($pos);
						$this->plugin->getEq($player);
						$c->set("busy", true);
						$c->save();
						$this->plugin->getScheduler()->scheduleRepeatingTask(new Asker($this->plugin, $player), 5);
						$this->plugin->getLogger()->info("Asker Task hat den Wert '$pname' bekommen.");
				}
			}
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());
		}

	}

}

class Asker extends Task
{
	public $plugin;
	public $player;

	public function __construct(Main $plugin, Player $player)
	{

		/**
		 * @param Main $plugin
		 * @param Player $player
		 */

		$this->plugin = $plugin;
		$this->player = $player;
		$maxteams = [1, 2, 3, 4, 5, 6, 7, 8];
		foreach($maxteams as $team) {
			$teamvar = "t$team";
			$this->$teamvar = 0;
		}
	}

	public function onRun(int $tick)
	{
		$player = $this->player;
		$name = $player->getName();
		$c = new Config("/cloud/users/$name.yml", Config::YAML);
		$pos = (int)$c->get("pos");
		$wool = (bool)$c->get("wool");
		$height = $player->getY();
		$arena = $player->getLevel();
		$arenaname = $arena->getFolderName();
		$ca = new Config("/cloud/pw/$arenaname.yml");
		if(!$player->isOnline()) {
			$this->plugin->getLogger()->info("Task für $name beendet!");
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());
		}

		$players = $this->plugin->getServer()->getOnlinePlayers();
		$counter = 0;
		foreach($players as $person) {
			if($person->getLevel()->getFolderName() == $arenaname) {
				$counter++;
				$pname = $person->getName();
				$cc = new Config("/cloud/users/$pname.yml", Config::YAML);
				$cpos = (int)$cc->get("pos");
				$teamcountervar = "t$cpos";
				$this->$teamcountervar = $this->$teamcountervar++;
			}
		}

		// SCOREBOARD
		$woolmsg = $wool;
		$blank = "                                 ";
		if($wool == true) {
			$woolmsg = f::BOLD.f::GREEN."O".f::RESET;
		} else {
			$woolmsg = f::BOLD.f::RED."X".f::RESET;
		}
		$tpos = "t$pos";
		$teamamount = $this->$tpos+1;
		$player->addActionBarMessage(
			f::RESET.f::GREEN."$blank $blank Team: ".f::WHITE .$this->plugin->getColor((int)$pos)." (".$teamamount.")\n"
			.f::GREEN."$blank $blank Wolle: ".f::WHITE.$woolmsg."\n"
			.f::GREEN ."$blank $blank Spieler: ".f::WHITE.$counter."\n\n\n\n\n\n");

		if($counter == 1) {
			$player->sendMessage($this->plugin->prefix."Du bist der letzte Überlebende!");
			$lobby = $this->plugin->getServer()->getDefaultLevel();
			$pos = new Position($lobby->getSafeSpawn()->getX(), $lobby->getSafeSpawn()->getY(),
				$lobby->getSafeSpawn()->getZ(), $lobby);
			$arena->unload();
			$this->plugin->getServer()->loadLevel($arenaname);
			$level = $this->plugin->getServer()->getLevelByName($arenaname);
			$level->setAutoSave(false);
			$player->teleport($pos);
			$player->getInventory()->clearAll();
			$ca->set("busy", false);
			$ca->save();
			$c->set("pos", false);
			$this->plugin->getScheduler()->cancelTask($this->getTaskId());

		}
		if($height < 0) {
			if($wool == true) {
				$spawn = (array)$ca->get("p$pos");
				$pos = new Position($spawn['0'], $spawn['1'], $spawn['2']);
				$player->teleport($pos);
				$this->plugin->getEq($player);
				$players = $this->plugin->getServer()->getOnlinePlayers();
				foreach($players as $person) {
					if ($person->getLevel()->getFolderName() == $arenaname) {
						$person->sendMessage($this->plugin->prefix."$name viel ins Große Nichts.");
					}
				}
			} else {
				$player->sendMessage(f::BOLD.f::RED."Du bist Gestorben!");
				$lobby = $this->plugin->getServer()->getDefaultLevel();
				$pos = new Position($lobby->getSafeSpawn()->getX(), $lobby->getSafeSpawn()->getY(),
				$lobby->getSafeSpawn()->getZ(), $lobby);
				$player->teleport($pos);
				$player->setSpawn($pos);
				$player->getInventory()->clearAll();
				$players = $this->plugin->getServer()->getOnlinePlayers();
				$counter = 0;
				foreach($players as $person) {
					if($person->getLevel()->getFolderName() == $arenaname) {
						$counter++;
						$pename = $person->getName();
						$pc = new Config("/cloud/users/$pename.yml", Config::YAML);
						$pos = $pc->get("pos");
						$person->sendMessage($this->plugin->prefix."$name von Team ist Ausgeschieden!");
					}
				}
				foreach($players as $person) {
					if ($person->getLevel()->getFolderName() == $arenaname) {
						$person->sendMessage($this->plugin->prefix."Es sind noch $counter Spieler übrig!");
					}
				}
				$this->plugin->getLogger()->info("$name");
				$this->plugin->getScheduler()->cancelTask($this->getTaskId());
			}
		}


	}
}

class SignUpdater extends Task
{
	public $plugin;
	public $sign;

	public function __construct(Main $plugin, \pocketmine\tile\Sign $sign)
	{
		/**
		 * @param Main $plugin
		 * @param \pocketmine\tile\Sign $sign
		 */

		$this->plugin = $plugin;
		$this->sign = $sign;
	}

	public function onRun(int $tick)
	{
		$sign = $this->sign;
		$text = $sign->getText();
		$levelname = $text['3'];
		$c = new Config("/cloud/pw/$levelname.yml", Config::YAML);
		$busy = (string)$c->get("busy");
		$this->plugin->getServer()->loadLevel($levelname);
		$level = $this->plugin->getServer()->getLevelByName($levelname);
		$level->setAutoSave(false);
		$players = $this->plugin->getServer()->getOnlinePlayers();
		$counter = 0;
		foreach($players as $player) {
			if($player->getLevel()->getFolderName() == $levelname) {
				$counter++;
			}
		}
		$dimension = $c->get("dimension");
		$playeramout = eval("return $dimension;");
		$sign->setLine(2, f::YELLOW."$counter ".f::DARK_GRAY."/ ".f::GREEN."$playeramout");
		if($busy) {
			$sign->setLine(0, f::RED."PushWars");
		}
		if(!$busy) {
			$sign->setLine(0, f::YELLOW."Push".f::GOLD."Wars");
		}
	}
}