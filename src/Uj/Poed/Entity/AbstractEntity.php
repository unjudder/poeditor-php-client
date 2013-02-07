<?php
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
}