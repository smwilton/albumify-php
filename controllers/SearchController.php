<?php

    require_once PROJECT_ROOT_PATH . "/utils/RequestResponseUtils.php";
    require_once PROJECT_ROOT_PATH . "/services/SearchService.php";

    
    class SearchController {
        
        private $searchService;
        private $requestResponseUtils;

        function __construct() {
            $this->requestResponseUtils = new RequestResponseUtils();
            $this->searchService = new SearchService();
        }

        function route($request, $method) {
            
            if($method == "GET") {
                $this->doSearch($request);
            } else {
                $requestResponseUtils->return405MethodNotAllowed();
            }
        
        }

        function doSearch() {
            if(isSet($_GET['album']) || isSet($_GET['artist'])) {
                $album = isset($_GET['album']) ? $_GET['album'] : null;
                $artist = isset($_GET['artist']) ? $_GET['artist'] : null;
                $yearFrom = isset($_GET['year_from']) ? $_GET['year_from'] : null;
                $yearTo = isset($_GET['year_to']) ? $_GET['year_to'] : null;
                $genre = isset($_GET['genre']) ? $_GET['genre'] : null;
                $subGenre = isset($_GET['sub_genre']) ? $_GET['sub_genre'] : null;
                $albums = $this->searchService->search($album, $artist, $yearFrom, $yearTo, $genre, $subGenre);
                $this->requestResponseUtils->returnResponse(json_encode($albums), 200);
            } else {
                $this->requestResponseUtils->return400BadRequest("Parameters 'from' and 'to' need to be supplied");
            }
        }


    }

?>