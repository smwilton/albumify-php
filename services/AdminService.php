<?php

    require_once PROJECT_ROOT_PATH . "/services/Database.php";
    require_once PROJECT_ROOT_PATH . "/services/UserService.php";

    const GET_ALL_USERS = "SELECT * FROM user";
    const GET_ALL_ARTISTS = "SELECT * FROM artist";
    const GET_ARTIST = "SELECT * FROM artist WHERE artist_id = ?";
    const GET_LARGEST_ALBUM_NUMBER = "SELECT album_number FROM album ORDER BY album_number DESC LIMIT 1";
    const GET_REVIEW = "SELECT * FROM user_album_review WHERE user_album_review_id = ?";
    const GET_ALL_PENDING_REVIEWS = "SELECT user_album_review.*, album.album_name FROM user_album_review, album WHERE user_album_review.user_album_review_state_id = 1 AND album.album_id = user_album_review.album_id";
    const CREATE_ARTIST = "INSERT INTO artist (artist_name) VALUES (?)";
    const COUNT_ALBUMS = "SELECT COUNT(*) FROM album";
    const UPDATE_USER_ADMIN = "UPDATE user SET user_first_name = ?, user_last_name = ?, user_role_id = ? WHERE user_id = ?";
    const UPDATE_ALBUM = "UPDATE album SET album_year = ?, album_name = ?, album_spotify_id = ? WHERE album_id = ?";
    const UPDATE_ARTIST = "UPDATE artist SET artist_name = ? WHERE artist_id = ?";
    const UPDATE_PENDING_REVIEW = "UPDATE user_album_review SET user_album_review_state_id = ? WHERE user_album_review_id = ?";
    const REMOVE_USER = "DELETE FROM user WHERE user_id = ?";
    const REMOVE_ALBUM = "DELETE FROM album WHERE album_id = ?";
    const REMOVE_ARTIST = "DELETE FROM artist WHERE artist_id = ?";

    class AdminService {

        private $database;
        private $userService;
        private $albumService;
        private $requestResponseUtils;

        function __construct() {
            $this->database = Database::getInstance();
            $this->userService = new UserService();
            $this->albumService = new AlbumService();
            $this->requestResponseUtils = new RequestResponseUtils();
        }

        function authenticateAdmin() {
            $username = $this->userService->authenticateUser();
            $user = $this->userService->getUser($username);
            return $user['user_role_id'] == 2;
        }

        function getAllPendingReviews() {
            if($this->authenticateAdmin()) {
                return $this->database->select(GET_ALL_PENDING_REVIEWS, null);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function getAllUsers() {
            if($this->authenticateAdmin()) {
                $arrayOfUsers = $this->database->select(GET_ALL_USERS, null);
                // Loop through each user and remove the password from being sent
                foreach ($arrayOfUsers as $key => $val) {
                    unset($arrayOfUsers[$key]['user_password']);
                }
                return $arrayOfUsers;
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function getAllArtists() {
            if($this->authenticateAdmin()) {
                return $this->database->select(GET_ALL_ARTISTS, null);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function createNewAlbum($album_year, $album_name, $artist_id, $spotifyId, $genreIds, $subgenreIds) {
            if($this->authenticateAdmin()) {
                $currentHighestNumber = $this->database->select(GET_LARGEST_ALBUM_NUMBER, null)[0]['album_number'];
                $this->database->saveAlbumToDB($currentHighestNumber + 1, $album_year, $album_name, $artist_id, $spotifyId, $genreIds, $subgenreIds);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function createNewArtist($artistName) {
            if($this->authenticateAdmin()) {
                $this->database->insert(CREATE_ARTIST, ["s", [$artistName]]);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }
        
        function updateUser($userId, $userFirstName, $userLastName, $userRoleId) {
            if($this->authenticateAdmin()) {
                $this->database->update(UPDATE_USER_ADMIN, ["ssii", [$userFirstName, $userLastName, $userRoleId, $userId]]);
                return $this->userService->getUserById($userId);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function updateAlbum($albumId, $albumYear, $albumName, $albumSpotifyId) {
            if($this->authenticateAdmin()) {
                $this->database->update(UPDATE_ALBUM, ["issi", [$albumYear, $albumName, $albumSpotifyId, $albumId]]);
                return $this->albumService->getAlbumNoArtist($albumId);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function updateArtist($artistId, $artistName) {
            if($this->authenticateAdmin()) {
                $this->database->update(UPDATE_ARTIST, ["si", [$artistName, $artistId]]);
                return $this->database->select(GET_ARTIST, ["i", [$artistId]]);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function updatePendingReview($userAlbumReviewId, $userAlbumReviewState) {
            if($this->authenticateAdmin()) {
                $this->database->update(UPDATE_PENDING_REVIEW, ["ii", [$userAlbumReviewState, $userAlbumReviewId]]);
                return $this->database->select(GET_REVIEW, ["i", [$userAlbumReviewId]]);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function removeUser($userId) {
            if($this->authenticateAdmin()) {
                return $this->database->insert(REMOVE_USER, ["i", [$userId]]);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function removeAlbum($albumId) {
            if($this->authenticateAdmin()) {
                $this->database->insert(REMOVE_ALBUM, ["i", [$albumId]]);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }

        function removeArtist($artistId) {
            if($this->authenticateAdmin()) {
                return $this->database->insert(REMOVE_ARTIST, ["i", [$artistId]]);
            } else {
                $this->requestResponseUtils->return403Forbidden("Need to be a logged in admin");
            }
        }
    } 

?> 