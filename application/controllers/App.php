<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class App extends CI_Controller {
    public function index() {
        header('Cache-Control: no-store');

        $this->load->model('Room');
        
        $players['Player 1'] = urlencode($this->Room->getToken(Room::USER_PLAYER1));
        $players['Player 2'] = urlencode($this->Room->getToken(Room::USER_PLAYER2));
        $players['Spectator'] = urlencode($this->Room->getToken(Room::USER_SPECTATOR));

        $server_name = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_URL);
        $port = filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_NUMBER_INT);
        
        if($server_name === 'localhost') {
            $URL = 'http://'.$server_name.':'.$port.'/bt/room.php?token=';
        } else {
            $URL = 'http://'.$server_name.'/room.php?token=';
        }
        
        $data = array (
            'players' => $players,
            'URL' => $URL
        );
        
        $this->load->view('enter', $data);
    }
    public function room($token) {
        
        $this->load->model('Room');
        
        $this->Room->loadToken($token);
        
        $player = $this->Room->player;
        
        function getPlayerName( $player ) {
            if ($player === 7) {
                return "a spectator";
            } else {
                return "player " . ($player + 1);
            }
        }
        
        $data = array (
            'player' => $player,
            'token' => json_encode($token),
            'playerName' => getPlayerName($player)
        );
        
        $this->load->view('room', $data);
    }
    
    public function assets($dir, $resource) {
        header('Content-Type: text/css');
        include 'application/public/'.$dir.'/'.$resource;
    }
}