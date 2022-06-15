<?php

    const FIND_BY_ALBUM_NAME = "SELECT * FROM album WHERE album_name LIKE ?";
    const FIND_BY_ARTIST_NAME = "SELECT * FROM album WHERE artist_id IN (SELECT artist_id FROM artist WHERE artist_name LIKE ?)";
    const PARTIAL_FIND_BY_YEAR = " AND album_year BETWEEN ? AND ?";
    const PARTIAL_FIND_BY_GENRE = " AND album_id IN (SELECT album_id FROM album_genre WHERE genre_id IN (SELECT genre_id FROM genre WHERE genre_name = ?))";
    const PARTIAL_FIND_BY_SUB_GENRE = " AND album_id IN (SELECT album_id FROM album_sub_genre WHERE sub_genre_id IN (SELECT sub_genre_id FROM sub_genre WHERE sub_genre_name = ?))";

    class SearchService {

        private $database;
        private $requestResponseUtils;

        function __construct() {
            $this->database = Database::getInstance();
            $this->requestResponseUtils = new RequestResponseUtils();
        }

        function search($album, $artist, $yearFrom, $yearTo, $genre, $subGenre) {
            
            // Base query (Album or Artist), return empty array if neither is given
            $searchString = "";
            $queryTypes = "";
            $queryValues = [];
            if(isset($album)) {
                $searchString = $searchString.FIND_BY_ALBUM_NAME;
                $queryTypes = $queryTypes."s"; 
                $queryValues[] = '%'.$album.'%';
            } else if(isset($artist)) {
                $searchString = $searchString.FIND_BY_ARTIST_NAME;
                $queryTypes = $queryTypes."s";
                $queryValues[] = '%'.$artist.'%';
            } else {
                return [];
            }

            // Filter: Year
            if(isset($yearFrom) && isset($yearTo)) {
                $searchString = $searchString.PARTIAL_FIND_BY_YEAR;
                $queryTypes = $queryTypes."ii"; 
                $queryValues[] = $yearFrom;
                $queryValues[] = $yearTo;
            }

            // Filter: Genre
            if(isset($genre)) {
                $searchString = $searchString.PARTIAL_FIND_BY_GENRE;
                $queryTypes = $queryTypes."s"; 
                $queryValues[] = $genre;
            }

            // Filter: Genre
            if(isset($subGenre)) {
                $searchString = $searchString.PARTIAL_FIND_BY_SUB_GENRE;
                $queryTypes = $queryTypes."s"; 
                $queryValues[] = $subGenre;
            }

            return $this->database->select($searchString, [$queryTypes, $queryValues]);
        }
    }
?>