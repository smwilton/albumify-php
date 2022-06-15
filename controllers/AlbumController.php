<?php

    require_once PROJECT_ROOT_PATH . "/utils/RequestResponseUtils.php";
    require_once PROJECT_ROOT_PATH . "/services/AlbumService.php";

    class AlbumController {

        private $albumService;
        private $userService;
        private $requestResponseUtils;

        function __construct() {
            $this->albumService = new AlbumService();
            $this->userService = new UserService();
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

            switch ($pathSectionsSize) {
                case 3:
                    # GET /album (Gets all albums betweem 'from' and 'to' in path)
                    $this->getAlbums($request);
                    break;
                case 4:
                    if(is_numeric($pathSections[3])) {
                        # GET /album/<ALBUM_ID>
                        $this->getSingleAlbum($request);
                        break;
                    } else if($pathSections[3] == "album-genres-sub-genres") {
                        # GET /album/album-genres-sub-genres
                        $this->getAllGenresAndSubGenres();
                        break;
                    } else {
                        $this->requestResponseUtils->return404NotFound();
                        break;    
                    }
                default:
                    $this->requestResponseUtils->return404NotFound();
                    break;
            }
            
            return "";
        }

        // ///////////// //
        //  POST ROUTES  //
        // ///////////// //
        function postRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            $body = json_decode(file_get_contents("php://input"),true);
            
            if((sizeof($pathSections) == 5 || sizeof($pathSections) == 6) && is_numeric($pathSections[3])) {
                switch ($pathSections[4]) {
                    case "rating":
                        # POST /album/:id/rating (Adds a new rating to an album)
                        $this->addRatingToAlbum($pathSections[3], $body);
                        break;
                    case "review":
                        # POST /album/:id/review (Adds a new review to an album)
                        $this->addReviewToAlbum($pathSections[3], $body);
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
            
            // All PUT requests to /user should be of size 3 or 4
            if((sizeof($pathSections) == 5 || sizeof($pathSections) == 6) && is_numeric($pathSections[3]) && $pathSections[4] == "rating") {
                # PUT /album/:id/rating
                $this->updateAlbumRating($pathSections[3], $body);
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

            if((sizeof($pathSections) == 5 || sizeof($pathSections) == 6) && is_numeric($pathSections[3])) {
                switch ($pathSections[4]) {
                    case "rating":
                        # DELETE /album/:id/rating (Removes a users rating from an album)
                        $this->removeRatingOfAnAlbum($pathSections[3]);
                        break;
                    case "review":
                        # DELETE /album/:id/review (Removes a users review from an album)
                        $this->removeReviewOfAnAlbum($pathSections[3]);
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

        function addRatingToAlbum($albumId, $rating) {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $this->albumService->rateAlbum($albumId, $rating);
                $this->requestResponseUtils->returnResponse("Rating submitted", 200);
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User not logged in");
            }
        }

        function removeRatingOfAnAlbum($albumId) {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $this->albumService->removeRatingFromAlbum($albumId);
                $this->requestResponseUtils->returnResponse("Rating removed", 200);
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User not logged in");
            }
        }

        function removeReviewOfAnAlbum($albumId) {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $this->albumService->removeReviewFromAlbum($albumId);
                $this->requestResponseUtils->returnResponse("Review removed", 200);
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User not logged in");
            }
        }

        function updateAlbumRating($albumId, $rating) {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $this->albumService->updateAlbumRating($albumId, $rating);
                $this->requestResponseUtils->returnResponse("Rating updated", 200);
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User not logged in");
            }
        }

        function addReviewToAlbum($albumId, $review) {
            $this->albumService->reviewAlbum($albumId, $review);
            $this->requestResponseUtils->returnResponse("Review submitted", 200);
        }

        function getSingleAlbum($request) {
            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            if(is_numeric($pathSections[3])) {
                $album = $this->albumService->getFullAlbum($pathSections[3]);
                $this->requestResponseUtils->returnResponse(json_encode($album), 200);
            } else {
                $this->requestResponseUtils->return400BadRequest("ID needs to be an integer"); 
            }
        }

        function getAlbums($request) {

            if(!isSet($_GET['from']) || !isSet($_GET['to'])) {
                $this->requestResponseUtils->return400BadRequest("Parameters 'from' and 'to' need to be supplied");
            }

            $from = $_GET['from'];
            $to = $_GET['to'];
            $albums = $this->albumService->getAlbums($from, $to);
            $this->requestResponseUtils->returnResponse(json_encode($albums), 200);
        }

        function getAllGenresAndSubGenres() {
            $result = $this->albumService->getAllGenresAndSubGenres();
            $this->requestResponseUtils->returnResponse(json_encode($result), 200);
        }
    }
?>