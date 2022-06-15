<?php

    // https://docstore.mik.ua/orelly/webprog/pcook/ch08_10.htm
    // https://docstore.mik.ua/orelly/webprog/pcook/ch08_11.htm
    // https://stackoverflow.com/questions/8151893/php-cookie-doesnt-update

    require_once PROJECT_ROOT_PATH . "/services/Database.php";
    require_once PROJECT_ROOT_PATH . "/utils/RequestResponseUtils.php";

    const secret = "qubIsGreat"; // Used in md5 hash for cookie
    const GET_USER_BY_EMAIL = "SELECT * FROM user WHERE user_email = ?";
    const GET_USER_BY_ID = "SELECT * FROM user WHERE user_id = ?";
    const GET_USERS_REVIEWS = "SELECT user_album_review.album_id, user_album_review.user_album_review_state_id, user_album_review.user_album_review, album.album_name FROM user_album_review, album WHERE user_album_review.user_id = ? AND user_album_review.album_id = album.album_id";
    const GET_USERS_RATINGS = "SELECT album.album_id, album.album_name, user_album_rating.user_album_rating_rating FROM user_album_rating, album WHERE user_album_rating.user_id = ? AND user_album_rating.album_id = album.album_id";
    const GET_OWNED_ALBUMS = "SELECT album.album_id, album.album_name FROM album, owned_album WHERE owned_album.user_id = ? AND album.album_id = owned_album.album_id";
    const GET_WANTED_ALBUMS = "SELECT album.album_id, album.album_name FROM album, wanted_album WHERE wanted_album.user_id = ? AND album.album_id = wanted_album.album_id";
    const GET_FAVOURITE_ALBUMS = "SELECT album.album_id, album.album_name FROM album, favourite_album WHERE favourite_album.user_id = ? AND album.album_id = favourite_album.album_id";
    const ADD_USER = "INSERT INTO user(user_email, user_first_name, user_last_name, user_password, user_role_id, user_creation_time, user_edit_time, user_last_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    const UPDATE_USER = "UPDATE user SET user_email = ?, user_first_name = ?, user_last_name = ?, user_password = ?, user_edit_time = ? WHERE user_email = ?";
    const UPDATE_USER_NO_PASSWORD = "UPDATE user SET user_email = ?, user_first_name = ?, user_last_name = ?, user_edit_time = ? WHERE user_email = ?";
    const DELETE_USER = "DELETE FROM user WHERE user_email = ?";
    const UPDATE_LOGIN_TIMESTAMP = "UPDATE user SET user_last_login = ? WHERE user_email = ?";
    const ADD_FAVOURITE_ALBUM = "INSERT INTO favourite_album(album_id, user_id) VALUES (?, ?)";
    const ADD_OWNED_ALBUM = "INSERT INTO owned_album(album_id, user_id) VALUES (?, ?)";
    const ADD_WANTED_ALBUM = "INSERT INTO wanted_album(album_id, user_id) VALUES (?, ?)";
    const REMOVE_FAVOURITE_ALBUM = "DELETE FROM favourite_album WHERE album_id = ? AND user_id = ?";
    const REMOVE_OWNED_ALBUM = "DELETE FROM owned_album WHERE album_id = ? AND user_id = ?";
    const REMOVE_WANTED_ALBUM = "DELETE FROM wanted_album WHERE album_id = ? AND user_id = ?";
    const CHECK_IF_USER_EXISTS = "SELECT EXISTS(SELECT * from user WHERE user_email = ?)";

    class UserService {

        private $database;
        private $requestResponseUtils;

        function __construct() {
            $this->database = Database::getInstance();
            $this->requestResponseUtils = new RequestResponseUtils();
        }

        function registerUser($email, $first_name, $last_name, $password) {
            $user_role_id = 1; // REGISTERED_USER role
            $timestamp = date('Y-m-d h:i:s');
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            return $this->database->insert(ADD_USER, ["ssssisss", [$email, $first_name, $last_name, $hashedPassword, $user_role_id, $timestamp, $timestamp, $timestamp]]);
        }

        function loginUser($user,$pass) {
            if($this->validated_user($user,$pass)) {
                $this->setLoginCookie($user);
                $this->database->update(UPDATE_LOGIN_TIMESTAMP, ["ss", [date('Y-m-d h:i:s'), $user]]);
                $this->requestResponseUtils->returnResponse("User logged in", 200);
            }
        }

        function getUser($email) {
            $user = $this->database->select(GET_USER_BY_EMAIL, ["s", [$email]])[0];
            unset($user['user_password']);
            return $user;
        }

        function getUserById($userId) {
            $user = $this->database->select(GET_USER_BY_ID, ["i", [$userId]])[0];
            unset($user['user_password']);
            return $user;
        }

        function getFullUser($email) {
            $user = $this->getUser($email);
            $reviews = $this->database->select(GET_USERS_REVIEWS, ["i", [$user['user_id']]]);
            $ratings = $this->database->select(GET_USERS_RATINGS, ["i", [$user['user_id']]]);
            $ownedAlbums = $this->database->select(GET_OWNED_ALBUMS, ["i", [$user['user_id']]]);
            $wantedAlbums = $this->database->select(GET_WANTED_ALBUMS, ["i", [$user['user_id']]]);
            $favouriteAlbums = $this->database->select(GET_FAVOURITE_ALBUMS, ["i", [$user['user_id']]]);
            $user['reviews'] = $reviews;
            $user['ratings'] = $ratings;
            $user['owned_albums'] = $ownedAlbums;
            $user['wanted_albums'] = $wantedAlbums;
            $user['favourite_albums'] = $favouriteAlbums;
            return $user;
        }

        function validated_user($email,$pass) { 
            $result = $this->database->select(GET_USER_BY_EMAIL, ["s", [$email]]);
            if (sizeOf($result) == 1 && password_verify($pass, $result[0]['user_password'])) { 
                return $result;
            } else { 
                $this->requestResponseUtils->return401NotAuthenticated("Authentication failed");
            }
        }

        function setLoginCookie($username) {
            setcookie('login', $username.','.md5($username.secret), time()+3600*24, '/');
        }

        function unsetSetLoginCookie() {
            if ($_COOKIE['login']) {
                setcookie('login', $_COOKIE['login'], time() - 1, '/');
            }
        }

        function authenticateUser() {
            if (isSet($_COOKIE['login'])) {
                list($c_username,$cookie_hash) = explode(',',$_COOKIE['login']); 
                if (md5($c_username.secret) == $cookie_hash) {
                    return $c_username;
                } else {
                    $this->requestResponseUtils->return401NotAuthenticated("User not authenticated");
                } 
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User not authenticated");
            }
        }

        // Front end will redirect to /logout if there is email and/or password change.
        function updateUser($username, $user) {

            // We have two queries, one where we update password and one where we don't
            $updatingPassword = false;

            // 1. Check if email changed

            if($username != $user['user_email']) {
                // We need to make sure that this email does not already exist in our database
                if($this->checkIfUserExists($username)) {
                    $this->requestResponseUtils->return400BadRequest("User with this email already exsists");
                }
            }

            // 2. Check if password has changed
            $password = $user['user_password'];
            if($password != null) {
                $password = password_hash($password, PASSWORD_DEFAULT);
                $updatingPassword = true;
                $needsToBeLoggedOut = true;
            }

            if($updatingPassword) {
                $this->database->update(UPDATE_USER, ["ssssss", [$user['user_email'], $user['user_first_name'], $user['user_last_name'], $password, date('Y-m-d h:i:s'), $username]]);    
            } else {
                $this->database->update(UPDATE_USER_NO_PASSWORD, ["sssss", [$user['user_email'], $user['user_first_name'], $user['user_last_name'], date('Y-m-d h:i:s'), $username]]);    
            }

            $this->requestResponseUtils->returnResponse("User updated", 200);
        }

        function deleteMe() {
            $username = $this->authenticateUser();
            return $this->database->insert(DELETE_USER, ["s", [$username]]);
        }

        function checkIfUserExists($email) {
            $result = $this->database->select(CHECK_IF_USER_EXISTS, ["s", [$email]]);
            return $result == 1 ? true : false;
        }

        function addFavouriteAlbum($albumId) {
            $myEmail = $this->authenticateUser();
            $user = $this->getUser($myEmail);
            return $this->database->insert(ADD_FAVOURITE_ALBUM, ["ii", [$albumId, $user['user_id']]]);
        }

        function removeFavouriteAlbum($albumId) {
            $myEmail = $this->authenticateUser();
            $user = $this->getUser($myEmail);
            return $this->database->insert(REMOVE_FAVOURITE_ALBUM, ["ii", [$albumId, $user['user_id']]]);
        }

        function addOwnedAlbum($albumId) {
            $myEmail = $this->authenticateUser();
            $user = $this->getUser($myEmail);
            return $this->database->insert(ADD_OWNED_ALBUM, ["ii", [$albumId, $user['user_id']]]);
        }

        function removeOwnedAlbum($albumId) {
            $myEmail = $this->authenticateUser();
            $user = $this->getUser($myEmail);
            return $this->database->insert(REMOVE_OWNED_ALBUM, ["ii", [$albumId, $user['user_id']]]);
        }

        function addWantedAlbum($albumId) {
            $myEmail = $this->authenticateUser();
            $user = $this->getUser($myEmail);
            return $this->database->insert(ADD_WANTED_ALBUM, ["ii", [$albumId, $user['user_id']]]);
        }

        function removeWantedAlbum($albumId) {
            $myEmail = $this->authenticateUser();
            $user = $this->getUser($myEmail);
            return $this->database->insert(REMOVE_WANTED_ALBUM, ["ii", [$albumId, $user['user_id']]]);
        }
    } 

?>