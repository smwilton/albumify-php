<?php
    # Blend of these two:
    # https://code.tutsplus.com/tutorials/how-to-build-a-simple-rest-api-in-php--cms-37000
    # https://phpenthusiast.com/blog/the-singleton-design-pattern-in-php
    # https://stackoverflow.com/questions/12091971/how-to-start-and-end-transaction-in-mysqli

    const ADD_GENRE_TO_ALBUM = "INSERT INTO album_genre (album_id, genre_id) VALUES (?, ?)";
    const ADD_SUB_GENRE_TO_ALBUM = "INSERT INTO album_sub_genre (album_id, sub_genre_id) VALUES (?, ?)";
    const CREATE_ALBUM = "INSERT INTO album (album_number, album_year, album_name, artist_id, album_average_rating, album_ratings_count, album_spotify_id) VALUES (?, ?, ?, ?, ?, ?, ?)";

    class Database {

        private static $instance = null;
        private $connection = null;
    
        private function __construct() {
            try {
                $this->connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE_NAME);
                if ( mysqli_connect_errno()) {
                    throw new Exception("Could not connect to database.");   
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage());   
            }           
        }

        public static function getInstance() {
            if(!self::$instance) {
                self::$instance = new Database();
            }
            return self::$instance;
        }

        public function getConnection() {
            return self::$connection;
        }

        public function insert($query = "" , $params = []) {
            try {
                $stmt = $this->executeStatement($query, $params);
                $lastId = $this->connection->insert_id;
                return $lastId;
            } catch(Exception $e) {
                throw New Exception($e->getMessage());
            }
            return false;
        }

        public function update($query = "" , $params = []) {
            try {
                $stmt = $this->executeStatement($query, $params);
                $lastId = $this->connection->insert_id;
                return $lastId;
            } catch(Exception $e) {
                throw New Exception($e->getMessage());
            }
            return false;
        }
    
        public function select($query = "" , $params = []) {
            try {
                $stmt = $this->executeStatement($query, $params);
                $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); 
                $stmt->close();
                return $result;
            } catch(Exception $e) {
                throw New Exception($e->getMessage());
            }
            return false;
        }

        public function transaction($queries = [] , $params = [[]]) {

            $this->connection->begin_transaction();

            foreach ($queries as $key => $val) {
                $this->executeStatement($val, $params[$key]);
            }

            $this->connection->commit();
        }

        public function exists($query = "" , $params = []) {
            try {
                $stmt = $this->executeStatement($query, $params);
                return (bool) $stmt->get_result()->fetch_row();               
            } catch(Exception $e) {
                throw New Exception($e->getMessage());
            }
            return false;
        }
        
        public function saveAlbumToDB($albumCount, $albumYear, $albumName, $artistId, $spotifyId, $genreIds, $subgenreIds) {

            try {
                // Start transaction for rest
                $this->connection->begin_transaction();

                echo $artistId;

                $this->executeStatement(CREATE_ALBUM, ["iisiiis", [$albumCount, $albumYear, $albumName, $artistId, 0, 0, $spotifyId]]);
                $albumId = $this->connection->insert_id;

                // Genres
                foreach ($genreIds as $key => $val) {
                    $this->executeStatement(ADD_GENRE_TO_ALBUM, ["ii", [$albumId, $genreIds[$key]]]);
                }

                // Sub-genres
                foreach ($subgenreIds as $key => $val) {
                    $this->executeStatement(ADD_SUB_GENRE_TO_ALBUM, ["ii", [$albumId, $subgenreIds[$key]]]);
                }

                $this->connection->commit();
            } catch(Exception $e) {
                $this->requestResponseUtils->return500InternalServerError($e->getMessage());
            }
        }
    
        # Creates a prepared statement
        private function executeStatement($query, $params) {
            try {
                $stmt = $this->connection->prepare($query);
    
                if($stmt === false) {
                    throw New Exception("Unable to do prepared statement: " . $query);
                }
                
                if( $params ) {
                    $stmt->bind_param($params[0], ...$params[1]);
                }
    
                $stmt->execute();
                
                return $stmt;
            } catch(Exception $e) {
                throw New Exception($e->getMessage());
            }   
        }
    }
?>