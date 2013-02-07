<?php
/**
 * Unjudder Api Client Module for Poeditor.com on top of Zendframework 2
 *
 * @link http://github.com/unjudder/poeditor-php-client for the canonical source repository
 * @copyright Copyright (c) 2013 unjudder
 * @license http://unjudder.com/license/new-bsd New BSD License
 * @package Uj\Poed
 */
namespace Uj\Poed\Entity;

abstract class AbstractEntity
{
	public function __construct(array $data = null)
	{
		if ($data !== null) {
			$this->importData($data);
		}
	}

	public function importData(array $data)
	{
		foreach ($data as $property => $value) {
			$setter = 'set' . ucfirst($property);
			$this->$setter($value);
		}
	}

	public static function fromArray(array $data)
	{
		return new static($data);
	}

	public function toArray()
	{
		return array();
	}
}