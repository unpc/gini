<?php

namespace Controller\CGI {

	class Error extends Layout {
	
		function __index($code = 404) {

			switch ($code) {
			case 401:
				$title = "Unauthorized visit";
				header($_SERVER["SERVER_PROTOCOL"]." 401 Unauthorized");
				header("Status: 401 Unauthorized");
				break;
			case 404:
				$title = "File not found";
				header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
				header("Status: 404 Not Found");
				break;
			}

			$this->view->title = $title;
			$this->view->body = V('phtml/error', array('code' => $code));
		}
	
	}

}
