<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
header('Cache-Control: no-store');

include 'Database.class.php';
include 'Room.class.php';
include 'maps.php';

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_URL);
$step = filter_input(INPUT_GET, 'step', FILTER_SANITIZE_NUMBER_INT);
$mapName = filter_input(INPUT_GET, 'map', FILTER_SANITIZE_STRING);

$roomObj = new Room($token);
$player = $roomObj->player;
$room = $roomObj->id;
$map = $mapList[$mapName];
assert($map !== NULL);

switch($step) {
    case 0:
    case 3:
    case 4:
        if($player !== Room::USER_PLAYER1) {
            echo json_encode( array(FALSE, $step));
            exit();
        }
        break;
    case 1:
    case 2:
    case 5:
        if($player !== Room::USER_PLAYER2) {
            echo json_encode( array(FALSE, $step));
            exit();
        }
        break;
    default:
        echo json_encode( array(FALSE, $step));
        exit();
}

$step++;

$database = new Database();
echo $database->ban($room, $player, $step, $map);