<?php
session_start();

/**
 * ComfyPark Request
 *
 * @author         Thierry Baumann <thierry.baumann@students.ffhs.ch>
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

				$response = $this->client->processLogin($data);
				if($response['success']) {
					$this->showHome($response);
				}

				$this->showLogin($response);
			break;

			case 'parking':
				die(json_encode($this->client->processParking($data)));
			break;

			case 'status':
				die(json_encode($this->client->processStatus()));
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

		die(strtr($data, array(
			'%ComfyParkJSON%' => json_encode(array(
				'site' => 'login',
				'data' => $options,
			)),
		)));
	}

	private function showHome($options = array()) {
		$data = file_get_contents(dirname(__FILE__) . '/templates/home.tmpl');

		die(strtr($data, array(
			'%ComfyParkJSON%' => json_encode(array(
				'site' => 'home',
				'data' => $options,
			)),
		)));
	}
}

$request = new Request();
$request->process();