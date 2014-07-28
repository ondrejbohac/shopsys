<?php

namespace SS6\ShopBundle\Model\Image\Config;

class ImageConfig {

	/**
	 * @var \SS6\ShopBundle\Model\Image\Config\ImageEntityConfig[]
	 */
	private $imageEntityConfigsByClass;

	/**
	 * @param \SS6\ShopBundle\Model\Image\Config\ImageEntityConfig[] $imageEntityInfoByClass
	 */
	public function __construct(array $imageEntityInfoByClass) {
		$this->imageEntityConfigsByClass = $imageEntityInfoByClass;
	}

	/**
	 * @param Object $entity
	 * @return string
	 */
	public function getEntityName($entity) {
		$entityConfig = $this->getImageEntityConfig($entity);
		return $entityConfig->getEntityName();
	}

	/**
	 * @param Object $entity
	 * @param string|null $type
	 * @param string|null $sizeName
	 * @return \SS6\ShopBundle\Model\Image\Config\ImageSizeConfig
	 */
	public function getImageSizeConfigByEntity($entity, $type, $sizeName) {
		$entityConfig = $this->getImageEntityConfig($entity);
		return $entityConfig->getTypeSize($type, $sizeName);
	}

	/**
	 * @param string $entityName
	 * @param string|null $type
	 * @param string|null $sizeName
	 * @return \SS6\ShopBundle\Model\Image\Config\ImageSizeConfig
	 */
	public function getImageSizeConfigByEntityName($entityName, $type, $sizeName) {
		$entityConfig = $this->getEntityConfigByEntityName($entityName);
		return $entityConfig->getTypeSize($type, $sizeName);
	}

	/**
	 * @param Object $entity
	 * @return \SS6\ShopBundle\Model\Image\Config\ImageEntityConfig;
	 * @throws \SS6\ShopBundle\Model\Image\Config\Exception\ImageEntityConfigNotFoundException
	 */
	public function getImageEntityConfig($entity) {
		$className = get_class($entity);
		if (array_key_exists($className, $this->imageEntityConfigsByClass)) {
			return $this->imageEntityConfigsByClass[$className];
		} else {
			throw new \SS6\ShopBundle\Model\Image\Config\Exception\ImageEntityConfigNotFoundException($className);
		}
	}

	/**
	 * @param string $entityName
	 * @return \SS6\ShopBundle\Model\Image\Config\ImageEntityConfig;
	 * @throws \SS6\ShopBundle\Model\Image\Config\Exception\ImageEntityConfigNotFoundException
	 */
	private function getEntityConfigByEntityName($entityName) {

		foreach ($this->imageEntityConfigsByClass as $entityConfig) {
			if ($entityConfig->getEntityName() === $entityName) {
				return $entityConfig;
			}
		}
		
		throw new \SS6\ShopBundle\Model\Image\Config\Exception\ImageEntityConfigNotFoundException($entityName);
	}

}
