<?php

/**
 * @author Fludixx
 * @version 2.1
 * @license MIT
 */

declare(strict_types=1);

namespace Fludixx\Bedwars\command;

use Fludixx\Bedwars\Arena;
use Fludixx\Bedwars\Bedwars;
use Fludixx\Bedwars\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BedWarsCommand extends Command {

    public $bedwars;

    public function __construct()
    {
        parent::__construct("bw",
            "BedWars Command",
            \null, ["bedwars"]);
        $this->bedwars = Bedwars::getInstance();
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!isset($args[0])){
            $sender->sendMessage("/bw create [ARENANAME] [MODE 8*1...]");
            return false;
        }
        if(isset($args[0]) && strtolower($args[0]) == "create" && !isset($args[1])){
            $sender->sendMessage("/bw create [ARENANAME] [MODE 8*1...]");
            return false;
        }
        switch (strtolower($args[0])){
            case "create":
                if(($sender->hasPermission("bw.admin") or $sender->isOp()) and $sender instanceof Player){
                    $player = Bedwars::$players[$sender->getName()];
                    if(!isset($args[0]) or !isset($args[1])) {
                        $sender->sendMessage(Bedwars::PREFIX."/bw create [ARENANAME] [MODE 8*1...]");
                        return false;
                    } else {
                        $levelname = $args[0];
                        $mode = $args[1];
                        $mode = str_replace("x", "*", $mode);
                        $maxplayers = eval("return ".$args[1].";");
                        if((int)$args[1][0] > 8) {
                            $player->sendMsg("You can't add more than 8 Teams");
                            return false;
                        } else {
                            if($this->bedwars->getServer()->loadLevel($levelname)) {
                                $level = $this->bedwars->getServer()->getLevelByName($levelname);
                                $arenadata = [
                                    'teams' => $mode[0],
                                    'ppt' => $mode[2],
                                    'mapname' => $levelname,
                                    'maxplayers' => $maxplayers,
                                    'spawns' => []
                                ];
                                Bedwars::$provider->addArena($levelname, $arenadata);
                                $player->setPos(-1);
                                $sender->getInventory()->setItem(0, Item::get(35, Utils::teamIntToColorInt(1)));
                                $sender->teleport($level->getSafeSpawn());
                                $player->sendMsg("Please Place the Blocks to set the Team spawns");
                                $player->sendMsg("use /leave to go to spawn or leave from setup");
                                Bedwars::$arenas[$player->getPlayer()->getLevel()->getFolderName()] =
                                    new Arena($player->getPlayer()->getLevel()->getFolderName(),
                                        (int)$mode[2], (int)$mode[0], $sender->getLevel(), []);
                                return true;
                            } else {
                                $player->sendMsg("Error: 1 Argument must be a Levelname!");
                                return false;
                            }
                        }
                    }
                } else {
                    $sender->sendMessage(Bedwars::PREFIX."You don't have the Permissions to add Arenas");
                }
                break;
            case "leave":
            case "l":
                if($sender instanceof Player) {
                    $player = Bedwars::$players[$sender->getName()];
                    $player->getPlayer()->setGamemode(0);
                    $player->rmScoreboard($sender->getLevel()->getFolderName());
                    $player->saveTeleport(Bedwars::getInstance()->getServer()->getDefaultLevel()->getSafeSpawn());
                    $player->setPos(0);
                    $player->setSpectator(FALSE);
                    $sender->getInventory()->setContents([
                        0 => Item::get(Item::IRON_SWORD)
                    ]);
                    $player->getPlayer()->getArmorInventory()->clearAll();
                }
                break;
            case "stats":
                if($sender instanceof Player) {
                    $stats = Bedwars::$statsSystem->getAll($sender);
                    foreach ($stats as $name => $vaule) {
                        if($name[0] !== ':') {
                            $sender->sendMessage("§a$name: §f$vaule");
                        }
                    }
                }
                break;
            case "setsign":
                if(($sender->hasPermission("bw.admin") or $sender->isOp()) and $sender instanceof Player){
                    if(!isset($args[0]) or $args[0] == "help") {
                        $sender->sendMessage(Bedwars::PREFIX."/bw setsign <arenaName>");
                        return false;
                    } else {
                        $arenas = Bedwars::$provider->getArenas();
                        if(!isset($arenas[$args[0]])) {
                            $sender->sendMessage(Bedwars::PREFIX."Arena not found! Be sure to register it");
                            return false;
                        } else {
                            $sender->sendMessage(Bedwars::PREFIX."Break a Sign");
                            Bedwars::$players[$sender->getName()]->setPos(-11);
                            Bedwars::$players[$sender->getName()]->setKnocker($args[0]);
                        }
                    }
                }
                break;
            case "start":
                if($sender->hasPermission("bw.start") and $sender instanceof Player) {
                    Bedwars::$arenas[$sender->getLevel()->getFolderName()]->setCountdown(10);
                }
                break;
            case "build":
                if($sender->hasPermission("bw.admin")) {
                    $mplayer = Bedwars::$players[$sender->getName()];
                    $mplayer->setCanBuild(!$mplayer->canBuild());
                    if($mplayer->canBuild()) {
                        $mplayer->sendMsg("You can now place & break blocks!");
                    } else {
                        $mplayer->sendMsg("You can't build now!");
                    }
                } else {
                    $sender->sendMessage("§cYou don't have the Permissions for this command");
                }
                break;
            case "help":
                if($sender->hasPermission("bw.admin") || $sender->isOp()){
                    $sender->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "BedWars Commands:\n" .
                        TextFormat::RESET . TextFormat::GREEN . "/bw create: " . TextFormat::GRAY . "create bedwars arena\n".
                        TextFormat::RESET . TextFormat::GREEN . "/bw setsign: " . TextFormat::GRAY . "set sign to arena\n".
                        TextFormat::RESET . TextFormat::GREEN . "/bw start: " . TextFormat::GRAY . "start your bedwars arena\n".
                        TextFormat::RESET . TextFormat::GREEN . "/bw build: " . TextFormat::GRAY . "enable build or disable build\n".
                        TextFormat::RESET . TextFormat::GREEN . "/bw leave: " . TextFormat::GRAY . "leave from bedwars arena\n".
                        TextFormat::RESET . TextFormat::GREEN . "/bw stats: " . TextFormat::GRAY . "lock your bedwars stats"
                    );
                    return false;
                } else {
                    $sender->sendMessage(TextFormat::GOLD . TextFormat::BOLD . "BedWars Commands:\n" .
                        TextFormat::RESET . TextFormat::GREEN . "/bw leave: " . TextFormat::GRAY . "leave from bedwars arena\n".
                        TextFormat::RESET . TextFormat::GREEN . "/bw stats: " . TextFormat::GRAY . "lock your bedwars stats"
                    );
                }
                break;
        }
    }
}
