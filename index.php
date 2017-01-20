<?php
session_start();

/**
 * ComfyPark Request
 *
 * @author         Thierry Baumann <thierry@swissmademarketing.com>
 * @copyright
 * @package        ComfyParkClient
 * @subpackage     Core
 */
require_once(dirname(__FILE__) . '/ComfyParkClient.php');

class Request{
	private $client;

	public function __construct() {
		$this->client = new ComfyParkClient();
	}

	public function process() {
		$data = $_REQUEST;

		if(!isset($data) || !$data) {
			if($this->client->isAuthenticated()){
				$this->showHome();
			}

			$this->showLogin();
		}

		$response = array();
		switch ($data['cmd']) {
			case 'login':
				if($this->client->isAuthenticated()){
					$this->showHome();
				}

				$response = $this->client->login($data);
				if($response['success']) {
					$this->showHome($response);
				}

				$this->showLogin($response);
			break;

			case 'parking':
				die(json_encode($this->client->parking($data)));
			break;

			case 'getStatus':
				die(json_encode($this->client->getStatus($data)));
			break;

			case 'logout':
				session_destroy();
				header("Location: " . "./");
			break;
		}

		if(!$response) {
			$this->showLogin();
		}
	}

	private function showLogin($options = array()) {
		$data = file_get_contents(dirname(__FILE__) . '/templates/login.tmpl');
		die($data);
	}

	private function showHome($options = array()) {
		$data = file_get_contents(dirname(__FILE__) . '/templates/home.tmpl');
		die($data);
	}
}

$request = new Request();
$request->process();