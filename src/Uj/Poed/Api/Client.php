<?php
/**
 * Unjudder Api Client Module for Poeditor.com on top of Zendframework 2
 *
 * @link http://github.com/unjudder/poeditor-php-client for the canonical source repository
 * @copyright Copyright (c) 2013 unjudder
 * @license http://unjudder.com/license/new-bsd New BSD License
 * @package Uj\Poed
 */
namespace Uj\Poed\Api;

use Zend\Http\Client as HttpClient;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Stdlib\ParametersInterface;
use Zend\Stdlib\Parameters;

use Uj\Poed\Api\TermsAddedResponse;

use Uj\Poed\Entity\Project;
use Uj\Poed\Entity\Term;
use Uj\Poed\Entity\Definition;

class Client
{
	const API_URI = 'http://poeditor.com/api/';

	const FORMAT_PO = 'po';
	const FORMAT_POT = 'pot';
	const FORMAT_MO = 'mo';
	const FORMAT_XLS = 'xls';
	const FORMAT_APPLE_STRINGS = 'apple_strings';
	const FORMAT_ANDROID_STRINGS = 'android_strings';
	const FORMAT_RESX = 'resx';
	const FORMAT_PROPERTIES = 'properties';

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

	public function getProjects()
	{
		$params = new Parameters(array('action' => 'list_projects'));
		$response = $this->_doRequest($params);
		$apiClient = $this;
		return array_map(function($project) use ($apiClient) {
			return new Project($project, $apiClient);
		}, $response['list']);
	}

	public function getProject($projectId)
	{
		$params = new Parameters(array(
			'action' => 'view_project',
			'id' => $projectId
		));
		$response = $this->_doRequest($params);

		return new Project($response['item'], $this);
	}

	public function getProjectLanguages($projectId)
	{
		$params = new Parameters(array(
			'action' => 'list_languages',
			'id' => $projectId
		));
		$response = $this->_doRequest($params);
		
		return $response['list'];
	}

	public function addProjectLanguage($projectId, $languageCode, &$message = null)
	{
		$params = new Parameters(array(
			'action' => 'add_language',
			'id' => $projectId,
			'language' => $languageCode
		));
		$response = $this->_doRequest($params);
		$message = $response['response']['message'];

		return $response['response']['status'] === 'success';
	}

	public function deleteProjectLanguage($projectId, $languageCode, &$message = null)
	{
		$params = new Parameters(array(
			'action' => 'delete_language',
			'id' => $projectId,
			'language' => $languageCode
		));
		$response = $this->_doRequest($params);
		$message = $response['response']['message'];
	
		return $response['response']['status'] === 'success';
	}

	public function getProjectTerms($projectId)
	{
		$params = new Parameters(array(
			'action' => 'view_terms',
			'id' => $projectId
		));
		$response = $this->_doRequest($params);

		return array_map(function ($term) {
			return new Term($term);
		}, $response['list']);
	}

	public function getProjectLanguageDefinitions($projectId, $languageCode)
	{
		$params = new Parameters(array(
			'action' => 'view_terms',
			'id' => $projectId,
			'language' => $languageCode
		));
		$response = $this->_doRequest($params);
		
		return array_map(function($data) {
			$definition = new Definition($data['definition']);
			unset($data['definition']);
			$definition->setTerm(new Term($data));
			return $definition;
		}, $response['list']);
	}

	public function addProjectTerms($projectId, array $terms)
	{
		$params = new Parameters(array(
			'action' => 'add_terms',
			'id' => $projectId,
			'data' => json_encode(array_map(function (Term $term) {
				return $term->toArray();
			}, $terms))
		));
		$response = $this->_doRequest($params);
		return new TermsAddedResponse($response['details']);
	}

	public function syncProjectTerms($projectId, array $terms)
	{
		$params = new Parameters(array(
			'action' => 'sync_terms',
			'id' => $projectId,
			'data' => json_encode(array_map(function (Term $term) {
				return $term->toArray();
			}, $terms))
		));
		$response = $this->_doRequest($params);
		return new TermsSynchronizedResponse($response['details']);
	}

	public function exportProjectLanguage($projectId, $languageCode, $format = self::FORMAT_MO)
	{
		$params = new Parameters(array(
			'action' => 'export',
			'id' => $projectId,
			'language' => $languageCode,
			'type' => $format
		));
		$response = $this->_doRequest($params);
		return $response;
	}
}
