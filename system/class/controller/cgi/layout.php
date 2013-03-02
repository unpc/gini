<?php

namespace Controller\CGI {

	class Layout extends \Controller\CGI {
		
		public $view;
		protected static $layout_name = 'phtml/layout';
		
		function __pre_action($action, &$params) {
			parent::__pre_action($action, $params);	
			$this->view = V(static::$layout_name);
			$this->view->title = _CONF('layout.title');	
		}
	
		function __post_action($action, &$params) {
			parent::__post_action($action, $params);
			echo $this->view;
		}
	
	}

}
