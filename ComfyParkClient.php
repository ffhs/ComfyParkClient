<?php
/**
 * ComfyParkClient
 * 
 * @author         Thierry Baumann <thierry@swissmademarketing.com>
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

		$this->userData = array();
		if($this->isAuthenticated()) {
			$this->user = $_SESSION['comfyParkClient']['auth']['user'];
			$this->userToken = $_SESSION['comfyParkClient']['auth']['userToken'];
		}
	}

	public function isAuthenticated() {
		return (bool)isset($_SESSION['comfyParkClient']['auth']['user']['uid']);
	}


	public function processLogin($data) {
		if($this->isAuthenticated()) {
			return array(
				'success' => true,
				'successMessage' => 'Login OK',
			);
		}

		if(!strlen($data['username']) || !strlen($data['password'])) {
			return array(
				'success' => false,
				'errorMessage' => 'Authentication failed: No username/password given',
			);
		}

		if($data['password']) {
			$data['password'] = $this->getHash($data['password'], false);
			$data['password'] = $this->getHash($data['password']);
		}

		$result = $this->runOperation($this->config['backend']['requests']['login']['url'], array(
			'username' => $data['username'],
			'password' => $data['password'],
		));

		if(isset($result) && $result['success']){
			$_SESSION['comfyParkClient']['auth']['user'] = $result['data']['user'];
			$_SESSION['comfyParkClient']['auth']['userToken'] = $result['data']['userToken'];

			return array(
				'success' => true,
				'successMessage' => $result['successMessage'],
			);
		}

		return array(
			'success' => false,
			'errorMessage' => $result['errorMessage'],
		);
	}

	public function processStatus() {
		return $this->runOperation($this->config['backend']['requests']['status']['url'], array(
			'userToken' => $this->userToken,
		));
	}

	public function processParking($data) {
		return $this->runOperation($this->config['backend']['requests']['parking']['url'], array(
			'userToken' => $this->userToken,
			'gateId' => $data['gateId'],
		));
	}

	private function runOperation($url, $data) {
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

		if($result = curl_exec($ch)) {
			if($result = json_decode($result, true)) {
				if($result['success']) {
					return array(
						'success' => true,
						'successMessage' => $result['message'],
						'data' => $result,
					);
				}

				return array(
					'success' => false,
					'errorMessage' => $result['message'],
					'data' => $result,
				);
			}
		}

		return array(
			'success' => false,
			'errorMessage' => 'Error - command couldn\'t be executed',
		);
	}

	private function getHash($data, $useSalt = true) {
		// add salt
		return hash('sha256', $data . ($useSalt ? $this->config['backend']['hashSalt'] : ''));
	}
}