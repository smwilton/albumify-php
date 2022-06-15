<?php

    class RequestResponseUtils {

        function getRequestPathSections($request) {
            $url_path = parse_url($request, PHP_URL_PATH);
            $url_path = explode( '/', $url_path);
            return $url_path;
        }

        function return400BadRequest($message) {
            http_response_code(400);
            echo "{ \"error\": \"" . $message . "\" }";
            die();
        }

        function return401NotAuthenticated($message) {
            http_response_code(401);
            echo "{ \"error\": \"" . $message . "\" }";
            die();
        }

        function return403Forbidden($message) {
            http_response_code(403);
            echo "{ \"error\": \"" . $message . "\" }";
            die();
        }

        function return404NotFound() {
            http_response_code(404);
            echo "{ \"error\": \"404 - Not found\" }";
            die();
        }

        function return405MethodNotAllowed() {
            http_response_code(405);
            echo "{ \"error\": \"HTTP method not supported\" }";
            die();
        }

        function return500InternalServerError($message) {
            http_response_code(500);
            echo "{ \"error\": \"" . $message . "\" }";
            die();
        }

        function returnResponse($message, $code) {
            http_response_code($code);
            echo $message;
            die();
        }

    }

?>