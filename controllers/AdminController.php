<?php

    require_once PROJECT_ROOT_PATH . "/utils/RequestResponseUtils.php";
    require_once PROJECT_ROOT_PATH . "/services/AdminService.php";

    class AdminController {

        private $adminService;
        private $requestResponseUtils;

        function __construct() {
            $this->adminService = new AdminService();
            $this->requestResponseUtils = new RequestResponseUtils();
        }

        function route($request, $method) {
            switch ($method) {
                case "GET":
                    $this->getRoutes($request);
                    break;
                case "POST":
                    $this->postRoutes($request);
                    break;
                case "PUT":
                    $this->putRoutes($request);
                    break;
                case "DELETE":
                    $this->deleteRoutes($request);
                    break;
                default:
                    $requestResponseUtils->return405MethodNotAllowed();
            }
        }

        // ///////////// //
        //   GET ROUTES  //
        // ///////////// //
        function getRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            $pathSectionsSize = sizeof($pathSections);
            if(sizeof($pathSections) == 4 || sizeof($pathSections) == 5) {
                switch ($pathSections[3]) {
                    case "pending-reviews":
                        # GET /admin/pending-reviews (Gets all pending reviews)
                        $this->getPendingReviews();
                        break;
                    case "users":
                        # GET /admin/users (Gets all users)
                        $this->getAllUsers();
                        break;
                    case "artists":
                        # GET /admin/artists (Gets all artists)
                        $this->getAllArtists();
                        break;
                    default:
                        $this->requestResponseUtils->return400BadRequest("Bad request");
                        break;
                }
            } else {
                $this->requestResponseUtils->return400BadRequest("Bad request");
            }
           
            return "";
        }

        // ///////////// //
        //  POST ROUTES  //
        // ///////////// //
        function postRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            $body = json_decode(file_get_contents("php://input"),true);

            if(sizeof($pathSections) == 4 || sizeof($pathSections) == 5) {
                switch ($pathSections[3]) {
                    case "album":
                        # POST /admin/album (Creates a new album)
                        $this->createNewAlbum($body['album_year'], $body['album_name'], $body['artist_id'], $body['spotify_id'], $body['genre_ids'], $body['sub_genre_ids']);
                        break;
                    case "artist":
                        # POST /admin/artist (Creates a new artist)
                        $this->createNewArtist($body['artist_name']);
                        break;
                    default:
                        $this->requestResponseUtils->return400BadRequest("Bad request");
                        break;
                }
            } else {
                $this->requestResponseUtils->return400BadRequest("Bad request");
            }

            return "";
        }

        // ///////////// //
        //   PUT ROUTES  //
        // ///////////// //
        function putRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            $body = json_decode(file_get_contents("php://input"),true);
            
            if(sizeof($pathSections) == 4 || sizeof($pathSections) == 5) {
                switch ($pathSections[3]) {
                    case "album":
                        # PUT /admin/album (Updates an album)
                        $this->updateAlbum($body['album_id'], $body['album_year'], $body['album_name'], $body['album_spotify_id']);
                        break;
                    case "artist":
                        # PUT /admin/artist (Updates an artist)
                        $this->updateArtist($body['artist_id'], $body['artist_name']);
                        break;
                    case "user":
                        # PUT /admin/user (Updates a user)
                        $this->updateUser($body['user_id'], $body['user_first_name'], $body['user_last_name'], $body['user_role_id']);
                        break;
                    case "pending-review":
                        # PUT /admin/pending-review (Updates a pending review)
                        $this->updatePendingReview($body['user_album_review_id'], $body['user_album_review_state_id']);
                        break;
                    default:
                        $this->requestResponseUtils->return400BadRequest("Bad request");
                        break;
                }
            } else {
                $this->requestResponseUtils->return400BadRequest("Bad request");
            }

            return "";
        }

        // ///////////// //
        // DELETE ROUTES //
        // ///////////// //
        function deleteRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            
            // All DELETE requests to /admin should be of size 5
            if(sizeof($pathSections) == 5) {
                switch ($pathSections[3]) {
                    case "user":
                        # DELETE /admin/user/:id (Removes a user)
                        $this->removeUser($pathSections[4]);
                        break;
                    case "album":
                        # DELETE /admin/user/:id (Removes an album)
                        $this->removeAlbum($pathSections[4]);
                        break;
                    case "artist":
                        # DELETE /admin/artist (Removes an artist)
                        $this->removeArtist($pathSections[4]);
                        break;
                    default:
                        $this->requestResponseUtils->return404NotFound();
                        break;
                }
            } else {
                return $this->requestResponseUtils->return404NotFound();
            }
            
            return "";
        }

        function getPendingReviews() {
            $pendingReviews = $this->adminService->getAllPendingReviews();
            $this->requestResponseUtils->returnResponse(json_encode($pendingReviews), 200);
        }

        function getAllUsers() {
            $users = $this->adminService->getAllUsers();
            $this->requestResponseUtils->returnResponse(json_encode($users), 200);
        }

        function getAllArtists() {
            $artists = $this->adminService->getAllArtists();
            $this->requestResponseUtils->returnResponse(json_encode($artists), 200);
        }

        function createNewAlbum($album_year, $album_name, $artist_id, $spotifyId, $genreIds, $subgenreIds) {
            $this->adminService->createNewAlbum($album_year, $album_name, $artist_id, $spotifyId, $genreIds, $subgenreIds);
            $this->requestResponseUtils->returnResponse("Album created", 200);
        }

        function createNewArtist($artistName) {
            $this->adminService->createNewArtist($artistName);
            $this->requestResponseUtils->returnResponse("Artist created", 200);
        }

        function updateAlbum($albumId, $albumYear, $albumName, $albumSpotifyId) {
            $album = $this->adminService->updateAlbum($albumId, $albumYear, $albumName, $albumSpotifyId);
            $this->requestResponseUtils->returnResponse(json_encode($album), 200);
        }

        function updateArtist($artistId, $artistName) {
            $artist = $this->adminService->updateArtist($artistId, $artistName);
            $this->requestResponseUtils->returnResponse(json_encode($artist), 200);
        }

        function updateUser($userId, $userFirstName, $userLastName, $userRoleId) {
            $user = $this->adminService->updateUser($userId, $userFirstName, $userLastName, $userRoleId);
            $this->requestResponseUtils->returnResponse(json_encode($user), 200);
        }

        function updatePendingReview($userAlbumReviewId, $userAlbumReviewState) {
            $review = $this->adminService->updatePendingReview($userAlbumReviewId, $userAlbumReviewState);
            $this->requestResponseUtils->returnResponse(json_encode($review), 200);
        }

        function removeUser($userId) {
            $this->adminService->removeUser($userId);
            $this->requestResponseUtils->returnResponse("User removed", 200);
        }

        function removeAlbum($albumId) {
            $this->adminService->removeAlbum($albumId);
            $this->requestResponseUtils->returnResponse("Album removed", 200);
        }

        function removeArtist($artistId) {
            $this->adminService->removeArtist($artistId);
            $this->requestResponseUtils->returnResponse("Artist removed", 200);
        }

    }
?>