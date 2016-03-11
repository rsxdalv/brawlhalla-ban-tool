<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Description of database
 *
 * @author rober
 */
class Database {
    private $db;
    
    
    private static $mapList = array('keep' => 1, 'pass' => 2, 'fortress' => 3, 'falls' => 4, 'hall' => 5, 'stadium' => 6, 'grove' => 7, 
            '1' => 'keep', '2' => 'pass', '3' => 'fortress', '4' => 'falls', '5' => 'hall', '6' => 'stadium', '7' => 'grove');

    
    public function __construct() {
        if( getenv('OPENSHIFT_PHP_IP') ) {    
            $mysql_host = getenv('OPENSHIFT_MYSQL_DB_HOST');
            $mysql_database = getenv('OPENSHIFT_APP_NAME');
            $mysql_user = getenv('OPENSHIFT_MYSQL_DB_USERNAME');
            $mysql_password = getenv('OPENSHIFT_MYSQL_DB_PASSWORD');
        } else if ($_SERVER['SERVER_NAME'] === 'localhost') {
            $mysql_host = 'localhost';
            $mysql_user = 'root';
            $mysql_password = 'ksex69';
            $mysql_database = 'brawl-draft-pick';
        } else {
            require 'configuration.php';
        }
        
        $this->db = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_database);
        if($this->db->connect_errno) {
            echo 'DB connection error. #'.$this->db->connect_errno.': '.$this->db->connect_error.PHP_EOL;
        }
    }
    
    public function ban($room, $player, $step, $map) {
        $duplicateQuery =   
                'SELECT *  
                FROM `ban_list` 
                WHERE `id` = '.($room | $map).';';

        $result = $this->db->query($duplicateQuery);
        if($result) {
            if($result->num_rows > 0) {
                return json_encode( array(FALSE, $step-1) );
            }
            $result->close();
        } else {
            $this->error($duplicateQuery);
        }

        $insertQuery =    
                'INSERT INTO `ban_list`
                (`id`, `room`, `player`, `map`, `step`) 
                VALUES ("'.($room | $map).'", "'.$room.'", "'.$player.'", "'.$map.'", "'.$step.'");';

        $result2 = $this->db->query($insertQuery);
        if($result2) {
            return json_encode( array(TRUE, $step) );
        } else {
            $this->error($insertQuery);
        }
    }
    public function getStep($room) {
        
    }
    public function listen($room, $step) {
        $listenQuery = 
                'SELECT `step` 
                FROM `ban_list` 
                WHERE `room` = ? 
                ORDER BY `step` DESC 
                LIMIT 1';

        // NB: Sleep time does not mess with PHP's max_execution_time on Linux, while on Windows this might be broken.
        $stmt = $this->db->prepare($listenQuery);
        $newStep = 0;
        if($stmt) 
        {
            $stmt->bind_param('i', $room);
            $stmt->bind_result($newStep);
            for($i = 0; $i < 150; $i++) { // 15 Second execution blocks
                $stmt->execute();
                $stmt->fetch();
                if($newStep > $step) {
                    break;
                }
                usleep(33333); // 30 Checks per second
            }
            $stmt->close();
            if($newStep > $step)
            {
                $maps = array();
                $maps[0] = $newStep;
                $readQuery =    
                        'SELECT map 
                        FROM `ban_list` 
                        WHERE `room` = '.$room.';';
                $readResult = $this->db->query($readQuery);
                if($readResult) {
                    while($row = $readResult->fetch_array()) {
                        $maps[] = self::$mapList[$row[0]];
                    }
                    $readResult->close();
                    return json_encode($maps);
                } else {
                    $this->error($readQuery);
                }
            } else {
                if($newStep === NULL) {
                    $newStep = 0;
                }
                return json_encode( array( NO_UPDATES, $newStep ) ); // No maps banned.
            }
        } else {
            $this->error($listenQuery);
        }
    }
    public function checkIn($room, $player) {
        
    }
    public function synchronize($room)
    {
        $listenQuery = 
                'SELECT `step` 
                FROM `ban_list` 
                WHERE `room` = '.$room.'
                ORDER BY `step` DESC 
                LIMIT 1';

        $listenResult = $this->db->query($listenQuery);
        if($listenResult) {
            if($listenResult->num_rows > 0) {
                $maps = array();
                $maps[0] = $listenResult->fetch_array()[0];
                $readQuery =    
                        'SELECT map 
                        FROM `ban_list` 
                        WHERE `room` = '.$room.';';
                
                $readResult = $this->db->query($readQuery);
                if($readResult) {
                    while($row = $readResult->fetch_array()) {
                        $maps[] = self::$mapList[$row[0]];
                    }
                    $readResult->close();
                    return json_encode($maps);
                } else {
                    $this->error($readQuery);
                }
            } else {
                return json_encode( array( NO_UPDATES, NO_MAPS_BANNED ) ); // No maps banned.
            }
        } else {
            $this->error($listenQuery);
        }
    }
    
    private function error($query) {
        echo "DB query error. #".$this->db->errno.": ".$this->db->error.PHP_EOL;
        echo "Query: ".$query.PHP_EOL;
        exit;
    }
}    