<?php

/**
 * Load/save one or more entities of a specified type
 */
class Shb_Dataflow_Model_Convert_Adapter_Entity
extends Mage_Eav_Model_Convert_Adapter_Entity
implements Mage_Dataflow_Model_Convert_Adapter_Interface
{

	private $_ops = array(
		'=' => 'eq',
		'!=' => 'neq',
		'>' => 'gt',
		'<' => 'lt',
		'>=' => 'gteq',
		'<=' => 'lteq'
	);

	public function load ()
	{
		$modelName = $this->getVar('model');
		//$map = $this->getVar('map');
		$model = Mage::getModel($modelName);
		$collection = $model->getCollection();

		//TODO: add selections and filters
		$filter = $this->getVar('filter');
		if (!empty($filter))
		{
			foreach ($filter as $field => &$value)
			{
				$value = explode(' ', trim($value), 2);
				if (count($value) == 2) {
					if (isset($this->_ops[$value[0]]))
						$value[0] = $this->_ops[$value[0]];
					$value = array( $value[0] => trim($value[1], "'") );
				}
				$collection->addFieldToFilter($field, $value);
			}
		}

		$batch = Mage::getSingleton('dataflow/batch');
		$export = $batch->getBatchExportModel();

		$count = 0;
		$ids = array();
		//$collection->addFieldToSelect('entity_id');
		foreach ($collection as $entity) {
			$ids[] = $entity->getId();
			$count++;
		}
		$this->setData($ids);

		$this->addException("Found {$count} entities of type '{$modelName}'");
	}
}
