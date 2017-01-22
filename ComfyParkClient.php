<?php
/**
 * ComfyParkClient
 * 
 * @author         Thierry Baumann <thierry.baumann@students.ffhs.ch>
 * @copyright
 * @package        ComfyParkClient
 * @subpackage     Core
 */
class ComfyParkClient {
	private $user;
	private $userToken;

	private $config;

	public function __construct() {
		// read main config
		require(__DIR__ . '/config.php');
		$this->config = $config;

		if($this->isAuthenticated()) {
			$this->user = $_SESSION['comfyPark']['auth']['user'];
			$this->userToken = $_SESSION['comfyPark']['auth']['userToken'];
		}
	}

	public function isAuthenticated() {
		return (bool)isset($_SESSION['comfyPark']['auth']['user']['uid']);
	}


	public function processLogin($data) {
		if($this->isAuthenticated()) {
			return array(
				'success' => true,
				'successMessage' => 'Login successful',
			);
		}

		if(!strlen($data['username']) || !strlen($data['password'])) {
			return array(
				'success' => false,
				'errorMessage' => 'Login failed, please check your username and password',
			);
		}

		if($data['password']) {
			$data['password'] = $this->getHash($data['password'], false);
			$data['password'] = $this->getHash($data['password']);
		}

		$response = $this->runOperation($this->config['backend']['requests']['login']['url'], array(
			'username' => $data['username'],
			'password' => $data['password'],
		));

		if(isset($response) && $response['success']){
			$_SESSION['comfyPark']['auth']['user'] = $response['responseData']['user'];
			$_SESSION['comfyPark']['auth']['userToken'] = $response['responseData']['userToken'];

			return array(
				'success' => true,
				'successMessage' => $response['successMessage'],
			);
		}

		return array(
			'success' => false,
			'errorMessage' => $response['errorMessage'],
		);
	}

	public function processStatus() {
		return $this->runOperation($this->config['backend']['requests']['status']['url'], array(
		), true);
	}

	public function processParking($data) {
		return $this->runOperation($this->config['backend']['requests']['parking']['url'], array(
			'gateId' => $data['gateId'],
		), true);
	}

	private function runOperation($url, $data = array(), $useToken = false) {
		if($useToken){
			// add user token to request
			$data['userToken'] = $this->userToken;

			// add request token to request
			$data['requestToken'] = $this->buildRequestToken($data);
		}

		$dataRequest = json_encode($data);

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataRequest);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($dataRequest),
			'x-api-key: ' . $this->config['backend']['apiKey'],
		));

		if($responseData = curl_exec($ch)) {
			if($responseData = json_decode($responseData, true)) {
				if($responseData['success']) {
					return array(
						'success' => true,
						'successMessage' => $responseData['message'],
						'responseData' => $responseData,
					);
				}

				return array(
					'success' => false,
					'errorMessage' => $responseData['message'],
					'responseData' => $responseData,
				);
			}
		}

		return array(
			'success' => false,
			'errorMessage' => 'Error - command couldn\'t be executed',
		);
	}

	private function buildRequestToken($data){
		$requestTokenValue = $this->user['uid'] . $this->user['countLogins'];

		foreach($data as $key => $value){
			if($key == 'requestToken') {
				continue;
			}

			$requestTokenValue .= $key . $value;
		}

		return $this->getHash($requestTokenValue);
	}

	private function getHash($data, $useSalt = true) {
		// add salt
		return hash('sha256', $data . ($useSalt ? $this->config['backend']['hashSalt'] : ''));
	}
}