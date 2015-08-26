<?php

/**
 * Parse/unparse entity between batch data and live objects
 */
class Shb_Dataflow_Model_Convert_Parser_Entity
extends Mage_Eav_Model_Convert_Parser_Abstract
implements Mage_Dataflow_Model_Convert_Parser_Interface
{
	protected $_recursion = 1;

	public function parse ()
	{
		throw new Exception("Unimplemented");
	}

	public function unparse ()
	{
		$entity_type = $this->getVar('model');
		$map = $this->getVar('map');

		$batch = $this->getBatchModel();
		$export = $batch->getBatchExportModel();

		$ids = $this->getData();
		$count = 0;
		foreach ($ids as $id)
		{
			$model = Mage::getModel($entity_type);
			//TODO: set store if appropriate
			$entity = $model->load($id);
			$data = $this->unparseEntity($entity);
			$batch->parseFieldList($data);
			$export->setId(NULL)
				->setBatchData($data)
				->save();
			$count++;
			$entity->clearInstance();
		}
		unset($model, $entity, $data);
		$this->addException("Loaded {$count} entities of type '{$entity_type}'");
	}

	private $_entities = array();
	/**
	 * Unparses a multi-level entity expanding any sub-entity accessible
	 * from the root entities through its getters.
	 */
	protected function unparseEntity ($entity, $prefix='')
	{
		if (empty($prefix)) {
			$this->_entities = array();
			$this->_recursion = $this->getVar('depth', 1);
		} else {
			$this->_recursion--;
		}

		$row = array();
		$id = $entity->getId();

		if (empty($this->_entities[get_class($entity)."#{$id}"]))
		{
			$this->_entities[get_class($entity)."#{$id}"] = true;

			$data = $entity->getData();
			foreach ($data as $key => &$value)
			{
				$baseKey = basename($key, '_id');
				$getField = 'get'.str_replace(' ', '', ucwords(str_replace('_', ' ', $baseKey)));
				if (method_exists($entity, $getField) && $this->_recursion >= 0)
					$value = $entity->$getField();
				if (is_object($value)) {
					$subdata = $this->unparseEntity($value, "{$prefix}{$baseKey}.");
					$row = array_merge($row, $subdata);
				} else {
					$row[$prefix.$key] = $value;
				}
			}
		}
		$this->_recursion++;
		return $row;
	}

}
