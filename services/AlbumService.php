<?php

    require_once PROJECT_ROOT_PATH . "/services/Database.php";

    const GET_ALBUM = "SELECT *, artist.artist_name FROM album, artist WHERE album.album_id = ? AND album.artist_id = artist.artist_id";
    const GET_ALBUM_NO_ARTIST = "SELECT * FROM album WHERE album.album_id = ?";
    const GET_ALBUMS = "SELECT *, artist.artist_name FROM album LEFT JOIN artist ON album.artist_id = artist.artist_id WHERE album.album_number between ? AND ? ORDER BY album.album_number";
    const GET_GENRES_FOR_ALBUM = "SELECT genre.genre_name FROM album, album_genre, genre WHERE album.album_id = ? AND album_genre.album_id = album.album_id AND album_genre.genre_id = genre.genre_id";
    const GET_SUB_GENRES_FOR_ALBUM = "SELECT sub_genre.sub_genre_name FROM album, album_sub_genre, sub_genre WHERE album.album_id = ? AND album_sub_genre.album_id = album.album_id AND album_sub_genre.sub_genre_id = sub_genre.sub_genre_id";
    const GET_REVIEWS_FOR_ALBUM = "SELECT user_album_review.album_id, user.user_email, user_album_review.user_album_review FROM user, user_album_review WHERE user_album_review.album_id = ? AND user_album_review.user_id = user.user_id AND user_album_review.user_album_review_state_id = 2";
    const GET_ALBUM_RATINGS = "SELECT user_album_rating_rating FROM user_album_rating WHERE album_id = ?";
    const GET_SPECIFIC_USER_ALBUM_RATING = "SELECT * from user_album_rating WHERE album_id = ? AND user_id = ?";
    const GET_SPECIFIC_USER_ALBUM_REVIEW = "SELECT * from user_album_review WHERE album_id = ? AND user_id = ?";
    const GET_ALL_GENRES = "SELECT * from genre";
    const GET_ALL_SUB_GENRES = "SELECT * from sub_genre";
    const ADD_RATING_TO_JOIN_TABLE = "INSERT INTO user_album_rating(user_album_rating_rating, album_id, user_id) VALUES (?, ?, ?)";
    const ADD_RATING_TO_ALBUM_TABLE = "UPDATE album SET album_average_rating = ?, album_ratings_count = ? WHERE album_id = ?";
    const ADD_REVIEW = "INSERT INTO user_album_review(user_album_review, user_id, user_album_review_state_id, album_id) VALUES (?, ?, ?, ?)";
    const UPDATE_RATING_IN_JOIN_TABLE = "UPDATE user_album_rating SET user_album_rating_rating = ? WHERE album_id = ? AND user_id = ?";
    const DELETE_RATING_IN_JOIN_TABLE = "DELETE FROM user_album_rating WHERE album_id = ? AND user_id = ?";
    const DELETE_REVIEW_IN_JOIN_TABLE = "DELETE FROM user_album_review WHERE album_id = ? AND user_id = ?";

    class AlbumService {

        private $database;
        private $userService;
        private $requestResponseUtils;

        function __construct() {
            $this->database = Database::getInstance();
            $this->userService = new UserService();
            $this->requestResponseUtils = new RequestResponseUtils();
        }

        function getAlbum($albumId) {
            $album = $this->database->select(GET_ALBUM, ["i", [$albumId]]);
            if($album != null) {
                return $album[0];
            } else {
                $this->requestResponseUtils->return404NotFound();
            }
        }

        function getAlbumNoArtist($albumId) {
            $album = $this->database->select(GET_ALBUM_NO_ARTIST, ["i", [$albumId]]);
            if($album != null) {
                return $album[0];
            } else {
                $this->requestResponseUtils->return404NotFound();
            }
        }

        function getFullAlbum($albumId) {
            $album = $this->database->select(GET_ALBUM, ["i", [$albumId]]);
            if($album != null) {
                $genres = $this->database->select(GET_GENRES_FOR_ALBUM, ["i", [$albumId]]);
                $subGenres = $this->database->select(GET_SUB_GENRES_FOR_ALBUM, ["i", [$albumId]]);
                $reviews = $this->database->select(GET_REVIEWS_FOR_ALBUM, ["i", [$albumId]]);

                $fullAlbum = $this->getAlbum($albumId);
                $fullAlbum['genres'] = $genres;
                $fullAlbum['sub_genres'] = $subGenres;
                $fullAlbum['reviews'] = $reviews; 

                return $fullAlbum;
            } else {
                $this->requestResponseUtils->return404NotFound();
            }
        }

        function getAlbums($from, $to) {
            return $this->database->select(GET_ALBUMS, ["ii", [$from, $to]]);
        }

        function rateAlbum($albumId, $rating) {
            $me = $this->getMe();
            $hasRatedBefore = $this->database->exists(GET_SPECIFIC_USER_ALBUM_RATING, ["ii", [$albumId, $me["user_id"]]]);
            if(!$hasRatedBefore) {
                $album = $this->getAlbum($albumId);

                // Get all votes for this album
                $ratings = $this->database->select(GET_ALBUM_RATINGS, ["i", [$albumId]]);
                $currentVotes = sizeof($ratings);

                // Get total ratings
                $totalRatingScores = 0;
                foreach ($ratings as $score) {
                    $totalRatingScores += $score['user_album_rating_rating'];
                }

                $newVoteCount = $currentVotes + 1;
                $newScore = ($totalRatingScores + $rating) / $newVoteCount;

                $queries = array(ADD_RATING_TO_JOIN_TABLE, ADD_RATING_TO_ALBUM_TABLE);
                $params = array(["dii", [$rating, $albumId, $me["user_id"]]], ["dii", [$newScore, $newVoteCount, $albumId]]);
                $this->database->transaction($queries, $params);
            } else {
                $this->requestResponseUtils->return400BadRequest("This user has rated this album before");
            }
        }

        function updateAlbumRating($albumId, $newRating) {
            $me = $this->getMe();
            $ratingExists = $this->database->exists(GET_SPECIFIC_USER_ALBUM_RATING, ["ii", [$albumId, $me["user_id"]]]);
            if($ratingExists) {

                // First get old album rating so we can calculate what to remove from the current rating
                $myOldRating = $this->database->select(GET_SPECIFIC_USER_ALBUM_RATING, ["ii", [$albumId, $me["user_id"]]])[0];
                $myOldScore = $myOldRating['user_album_rating_rating'];

                // Get album
                $album = $this->getAlbum($albumId);
                $currentAverageScore = $album['album_average_rating'];
                $currentVotesCount = $album['album_ratings_count'];

                // Calculate new weighted score
                $scoreWithoutMyOldRating = $currentAverageScore - ($myOldScore / $currentVotesCount);
                $updatedCurrentScore = $scoreWithoutMyOldRating + ($newRating / $currentVotesCount);

                // Add new score
                $queries = array(UPDATE_RATING_IN_JOIN_TABLE, ADD_RATING_TO_ALBUM_TABLE);
                $params = array(["dii", [$newRating, $albumId, $me["user_id"]]], ["dii", [$updatedCurrentScore, $currentVotesCount, $album]]);
                $this->database->transaction($queries, $params);
            } else {
                $this->requestResponseUtils->return400BadRequest("There exists no rating for this album by this user");
            }
        }

        function reviewAlbum($albumId, $review) {
            $me = $this->getMe();
            $hasReviewedBefore = $this->database->exists(GET_SPECIFIC_USER_ALBUM_REVIEW, ["ii", [$albumId, $me["user_id"]]]);
            if(!$hasReviewedBefore) {
                $id = $this->database->insert(ADD_REVIEW, ["siii", [$review, $me["user_id"], 1, $albumId]]);
                return $id;
            } else {
                $this->requestResponseUtils->return400BadRequest("This user has reviewed this album before");
            }
        }

        function removeRatingFromAlbum($albumId) {
            $me = $this->getMe();
            $ratingExists = $this->database->exists(GET_SPECIFIC_USER_ALBUM_RATING, ["ii", [$albumId, $me["user_id"]]]);
            if($ratingExists) {

                // First get my old album rating so we can calculate what to remove from the current rating
                $myRating = $this->database->select(GET_SPECIFIC_USER_ALBUM_RATING, ["ii", [$albumId, $me["user_id"]]])[0];
                $myScore = $myRating['user_album_rating_rating'];

                // Get all votes for this album
                $ratings = $this->database->select(GET_ALBUM_RATINGS, ["i", [$albumId]]);
                $currentVotes = sizeof($ratings);

                // Get total ratings
                $totalRatingScores = 0;
                foreach ($ratings as $score) {
                    $totalRatingScores += $score['user_album_rating_rating'];
                }

                // Calculate new values
                $newTotalRating = $totalRatingScores - $myScore;
                $newCurrentVotes = $currentVotes - 1;
                $newAverageScore = $newCurrentVotes == 0 ? 0 : ($newTotalRating / $newCurrentVotes);

                // Add new score
                $queries = array(DELETE_RATING_IN_JOIN_TABLE, ADD_RATING_TO_ALBUM_TABLE);
                $params = array(["ii", [$albumId, $me["user_id"]]], ["dii", [$newAverageScore, $newCurrentVotes, $albumId]]);
                $this->database->transaction($queries, $params);
            } else {
                $this->requestResponseUtils->return400BadRequest("There exists no rating for this album by this user");
            }
        }

        function removeReviewFromAlbum($albumId) {
            $me = $this->getMe();
            $hasReviewedBefore = $this->database->exists(GET_SPECIFIC_USER_ALBUM_REVIEW, ["ii", [$albumId, $me["user_id"]]]);
            if($hasReviewedBefore) {
                return $this->database->insert(DELETE_REVIEW_IN_JOIN_TABLE, ["ii", [$albumId, $me["user_id"]]]);
            } else {
                $this->requestResponseUtils->return400BadRequest("This user has not reviewed this album before");
            }
        }

        function getMe() {
            $myEmail = $this->userService->authenticateUser();
            $user = $this->userService->getUser($myEmail);
            return $user;
        }

        function getAllGenresAndSubGenres() {
            $genres = $this->database->SELECT(GET_ALL_GENRES, null);
            $subGenres = $this->database->SELECT(GET_ALL_SUB_GENRES, null);
            $results = [];
            $results['genres'] = $genres;
            $results['sub_genres'] = $subGenres;
            return $results;
        }

    } 

?> 