<?php

/**
 * Load/save one or more entities of a specified type
 */

class Shb_Dataflow_Model_Convert_Adapter_Entity
	extends Mage_Eav_Model_Convert_Adapter_Entity
{
	public function load ()
	{
		$entity_type = $this->getVar('entity_type');
		$map = $this->getVar('map');
		$model = Mage::getModel($entity_type);
		$collection = $model->getCollection();

		//TODO: add selections and filters
		$filter = $this->getVar('filter');
		if (!empty($filter))
		{
			foreach ($filter as $field => $value)
			{
				$collection->addFieldToFilter($field, $value);
			}
		}

		$batch = Mage::getSingleton('dataflow/batch');
		$export = $batch->getBatchExportModel();

		$count = 0;
		foreach ($collection as $entity)
		{
			$data = $entity->getData();
			$export->setId(NULL)
				->setBatchData($data)
				->save();
			/*if ($export->getId() && $count < 1)
				$batch->parseFieldList($data);*/
			$count++;
		}
		$this->addException("Loaded {$count} entities of type '{$entity_type}'");
	}
}
