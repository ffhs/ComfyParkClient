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
	private $config = array(
		'comfyParkBackend' => array(
			'url' => 'https://ei4i85h9ni.execute-api.eu-west-1.amazonaws.com/prod/comfyparkbackend',
			'apiKey' => 'UN00DjpQzp75pCbKZ3alu7aAIltVI0zt5Feoha3C',
		),
		'mysql' => array(
			'servername' => 'comfyparkdb.cjn6mrex9bqq.eu-west-1.rds.amazonaws.com',
			'username' => 'ffhs',
			'password' => 'glauer.ch',
			'dbname' => 'comfypark',
		),
	);

	private $userData;


	public function __construct() {
		$this->userData = array();
		if($this->isAuthenticated()) {
			$this->userData = $_SESSION['comfyParkClient']['auth']['user'];
		}
	}


	public function login($options) {
		if($this->isAuthenticated()) {
			return $this->error;
		}
		// login
		$username = $options['username'];
		$password = $options['password'];

		if(!strlen($username) || !strlen($password)) {
			return array(
				'success' => false,
				'errorMessage' => 'Authentication failed: No username/password given',
			);
		}

		// create mysql connection
		$handler = new mysqli($this->config['mysql']['servername'], $this->config['mysql']['username'], $this->config['mysql']['password'], $this->config['mysql']['dbname']);

		$stmt = $handler->prepare('SELECT * FROM users WHERE username = ? AND password = MD5(?)');

	   	// bind parameters
		$stmt->bind_param('ss', $username, $password);

		// execute query
		$stmt->execute();

		if($res = $stmt->get_result()) {
			for($rowNo = ($res->num_rows - 1); $rowNo >= 0; $rowNo --) {
			   	$res->data_seek($rowNo);
				$result = $res->fetch_assoc();
			}

			if($result) {
				$_SESSION['comfyParkClient']['auth']['user'] = $result;

				return array(
					'success' => true,
					'successMessage' => 'Login OK',
				);
			}
		}

		$stmt->close();

		return array(
			'success' => false,
			'errorMessage' => 'Authentication failed',
		);
	}


	public function isAuthenticated() {
		return (bool)isset($_SESSION['comfyParkClient']['auth']['user']['uid']);
	}



	public function getStatus($data) {
		return $this->callBackend(array_merge($data, array(
			'cmd' => 'getStatus',
		)));
	}

	public function parking($data) {
		return $this->callBackend(array_merge($data, array(
			'cmd' => 'parking',
		)));
	}


	private function callBackend($data) {
		$dataRequest = json_encode(array(
			'customerID' => md5($this->userData['uid']),
			'gateUUID' => $data['gate'],
			'cmd' => $data['cmd'],
		));

		$ch = curl_init($this->config['comfyParkBackend']['url']);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataRequest);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($dataRequest),
			'x-api-key: ' . $this->config['comfyParkBackend']['apiKey'],
		));

		if($result = curl_exec($ch)) {
			if($result = json_decode($result, true)) {
				if($result['success']) {
					return array(
						'success' => true,
						//'successData' => $result,
						'successMessage' => $result['result'],
						'gateAction' => $result['gateAction'],
						'timeIn' => $result['timeIn'],
					);
				}

				return array(
					'success' => false,
					//'successData' => $result,
					'errorMessage' => $result['result'],
				);
			}
		}

		return array(
			'success' => false,
			'errorMessage' => 'Error - command couldn\'t be executed',
		);
	}
}