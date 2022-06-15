<?php

    require_once PROJECT_ROOT_PATH . "/utils/RequestResponseUtils.php";
    require_once PROJECT_ROOT_PATH . "/services/UserService.php";

    class UserController {
    
        private $userService;
        private $requestResponseUtils;

        function __construct() {
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
                    $this->requestResponseUtils->return405MethodNotAllowed();
            }
        }

        // ///////////// //
        //   GET ROUTES  //
        // ///////////// //
        function getRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            
            // All GET requests to /user should be of size 3 or 4
            if(sizeof($pathSections) == 3 || (sizeof($pathSections) == 4 && $pathSections[3] == '')) {
                # GET /user
                $this->getMe();
            } else if(sizeof($pathSections) == 4) {
                switch ($pathSections[3]) {
                    case "logout":
                        # GET /user/logout
                        $this->logoutUser();
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

        // ///////////// //
        //  POST ROUTES  //
        // ///////////// //
        function postRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);

            // All POST requests to /user should be of size 4
            if(sizeof($pathSections) != 4) {
                return $this->requestResponseUtils->return404NotFound();
            }

            switch ($pathSections[3]) {
                case "register":
                    # POST /user/register (Registers a new user)
                    $this->registerUser();
                    break;
                case "login":
                    # POST /user/login (Logs in a user)
                    $this->loginUser();
                    break;
                case "favourite":
                    # POST /user/favourite (Adds a favourite album)
                    $this->addFavouriteAlbum();
                    break;
                case "owned":
                    # POST /user/owned (Add owned a album)
                    $this->addOwnedAlbum();
                    break;
                case "wanted":
                    # POST /user/wanted (Add wants an album)
                    $this->addWantedAlbum();
                    break;
                default:
                    $this->requestResponseUtils->return400BadRequest("Bad request");
                    break;
            }
            
            return "";
        }

        // ///////////// //
        //   PUT ROUTES  //
        // ///////////// //
        function putRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            
            // All PUT requests to /user should be of size 3 or 4
            if(sizeof($pathSections) == 3 || (sizeof($pathSections) == 4 && $pathSections[3] == '')) {
                # GET /user
                $this->updateMe();
            } else {
                return $this->requestResponseUtils->return400BadRequest("Bad request");
            }
            
            return "";
        }

        // ///////////// //
        // DELETE ROUTES //
        // ///////////// //
        function deleteRoutes($request) {

            $pathSections = $this->requestResponseUtils->getRequestPathSections($request);
            
            // All DELETE requests to /user should be of size 3 or 5
            if(sizeof($pathSections) == 3 || (sizeof($pathSections) == 4 && $pathSections[3] == '')) {
                # DELETE /user
                $this->deleteMe();
            } else if(sizeof($pathSections) == 5) {
                switch ($pathSections[3]) {
                    case "favourite":
                        # DELETE /user/favourite (Removes a favourated album)
                        $this->removeFavouriteAlbum($pathSections[4]);
                        break;
                    case "owned":
                        # DELETE /user/owned (Removes an owned album)
                        $this->removeOwnedAlbum($pathSections[4]);
                        break;
                    case "wanted":
                        # DELETE /user/wanted (Removes a desired album)
                        $this->removeWantedAlbum($pathSections[4]);
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

        function logoutUser() {
            $this->userService->unsetSetLoginCookie();
            $this->requestResponseUtils->returnResponse("User logged out", 200);
        }

        function loginUser() {
            $body = json_decode(file_get_contents("php://input"),true);
            $user = $this->userService->loginUser($body["email"], $body["password"]);
            $this->requestResponseUtils->returnResponse(json_encode($user), 200);
        }

        function registerUser() {
            $body = json_decode(file_get_contents("php://input"),true);
            $user = $this->userService->registerUser($body["email"], $body["first_name"], $body["last_name"], $body["password"]);
            $this->requestResponseUtils->returnResponse(json_encode($user), 201);
        }
        
        function getMe() {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $user = $this->userService->getFullUser($username);
                $this->requestResponseUtils->returnResponse(json_encode($user), 200);
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User not logged in");
            }
        }

        function updateMe() {
            $username = $this->userService->authenticateUser();
            $body = json_decode(file_get_contents("php://input"),true);
            if(!is_null($username)) {
                $user = $this->userService->updateUser($username, $body);
                $this->requestResponseUtils->returnResponse(json_encode($user), 200);
            }
        }

        function deleteMe() {
            $this->userService->deleteMe();
            $this->requestResponseUtils->returnResponse("User deleted", 200);
        }

        function addFavouriteAlbum() {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $body = json_decode(file_get_contents("php://input"),true);
                if(is_numeric($body)) {
                    $this->userService->addFavouriteAlbum($body);
                    $this->requestResponseUtils->returnResponse("Album favourited", 201);
                } else {
                    $this->requestResponseUtils->return400BadRequest("Variable needs to be a number");
                }
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User is not authenticated");
            }
        }

        function removeFavouriteAlbum($albumId) {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $body = json_decode(file_get_contents("php://input"),true);
                if(is_numeric($albumId)) {
                    $this->userService->removeFavouriteAlbum($albumId);
                    $this->requestResponseUtils->returnResponse("Album removed", 200);
                } else {
                    $this->requestResponseUtils->return400BadRequest("Variable needs to be a number");
                }
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User is not authenticated");
            }
        }

        function addOwnedAlbum() {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $body = json_decode(file_get_contents("php://input"),true);
                if(is_numeric($body)) {
                    $this->userService->addOwnedAlbum($body);
                    $this->requestResponseUtils->returnResponse("Album owned", 201);
                } else {
                    $this->requestResponseUtils->return400BadRequest("Variable needs to be a number");
                }
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User is not authenticated");
            }
        }

        function removeOwnedAlbum($albumId) {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $body = json_decode(file_get_contents("php://input"),true);
                if(is_numeric($albumId)) {
                    $this->userService->removeOwnedAlbum($albumId);
                    $this->requestResponseUtils->returnResponse("Album removed", 200);
                } else {
                    $this->requestResponseUtils->return400BadRequest("Variable needs to be a number");
                }
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User is not authenticated");
            }
        }

        function addWantedAlbum() {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $body = json_decode(file_get_contents("php://input"),true);
                if(is_numeric($body)) {
                    $this->userService->addWantedAlbum($body);
                    $this->requestResponseUtils->returnResponse("Album wanted", 201);
                } else {
                    $this->requestResponseUtils->return400BadRequest("Variable needs to be a number");
                }
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User is not authenticated");
            }
        }

        function removeWantedAlbum($albumId) {
            $username = $this->userService->authenticateUser();
            if(!is_null($username)) {
                $body = json_decode(file_get_contents("php://input"),true);
                if(is_numeric($albumId)) {
                    $this->userService->removeWantedAlbum($albumId);
                    $this->requestResponseUtils->returnResponse("Album removed", 200);
                } else {
                    $this->requestResponseUtils->return400BadRequest("Variable needs to be a number");
                }
            } else {
                $this->requestResponseUtils->return401NotAuthenticated("User is not authenticated");
            }
        }
    }

?>