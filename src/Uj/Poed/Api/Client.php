<?php
/**
 * Unjudder Api Client Module for Poeditor.com on top of Zendframework 2
 *
 * @link http://github.com/unjudder/poeditor-php-client for the canonical source repository
 * @copyright Copyright (c) 2013 unjudder
 * @license http://unjudder.com/license/new-bsd New BSD License
 * @package Uj\Poed\Api
 */
namespace Uj\Poed\Api;

use Zend\Http\Client as HttpClient;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Stdlib\ParametersInterface;
use Zend\Stdlib\Parameters;

use Uj\Poed\Entity\Project;

class Client
{
	const API_URI = 'http://poeditor.com/api/';

	/**
	 * @var string
	 */
	private $authToken = null;

	/**
	 * @var HttpClient
	 */
	protected $httpClient = null;

	public function __construct($authToken, HttpClient $httpClient = null)
	{
		$this->authToken = $authToken;

		if ($httpClient !== null) {
			$this->setHttpClient($httpClient);
		}
	}

	/**
	 * @return \Zend\Http\Client
	 */
	public function getHttpClient()
	{
		if ($this->httpClient === null) {
			$this->httpClient = new HttpClient;
		}

		return $this->httpClient;
	}

	/**
	 * @param HttpClient $httpClient
	 */
	public function setHttpClient(HttpClient $httpClient)
	{
		$this->httpClient = $httpClient;
	}

	protected function _doRequest(ParametersInterface $params)
	{
		$request = new HttpRequest;
		$request->setUri(self::API_URI);
		$request->setMethod(HttpRequest::METHOD_POST);
		$post = clone $params;
		$post->set('api_token', $this->authToken);
		$request->setPost($post);
		$request->getHeaders()->addHeaderLine('Content-Type', HttpClient::ENC_URLENCODED);

		return $this->_handleResponse(
			$this->getHttpClient()->send($request)
		);
	}

	protected function _handleResponse(HttpResponse $response)
	{
		if ($response->isSuccess()) {
			$jsonObj = json_decode($response->getBody(), true);
			if ($jsonObj['response']['status'] === 'fail') {
				throw new \Exception(
					$jsonObj['response']['message'],
					$jsonObj['response']['code']
				);
			} else {
				return $jsonObj;
			}
		} else {
			throw new \Exception(
				'Something bad happened: '.
				$response->getBody()	
			);
		}
	}

	public function listProjects()
	{
		$params = new Parameters(array('action' => 'list_projects'));
		$response = $this->_doRequest($params);

		return array_map(function($project) {
			return new Project($project);
		}, $response['list']);
	}
}
