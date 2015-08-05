<?php

/**
 * Expands stored entities referenced by id inside a dataflow batch.
 * Expanded entity field can be mapped, i.e. aliased, or prefixed to avoid
 * name collisions.
 */

class Shb_Dataflow_Model_Convert_Mapper_Entity
	extends Mage_Dataflow_Model_Convert_Mapper_Abstract
{
	public function map()
	{
		$batch = Mage::getSingleton('dataflow/batch');
		$export = $batch->getBatchExportModel();

		$ids = $export->setBatchId($batch->getId())->getIdCollection();

		$entity_type = $this->getVar('model');
		if (empty($entity_type)) {
			Mage::throwException("Undefined <em>model</em> type");
		}
		$prefix = $this->getVar('prefix', array_pop(explode('/', $entity_type)).'_');
		$entity_id = $this->getVar('entity_id', "{$prefix}id");
		$replace = $this->getVar('replace', true);
		$map = $this->getVar('map', array());

		$entity = Mage::getModel($entity_type);
		foreach ($ids as $id)
		{
			$export->load($id);
			$data = $export->getBatchData();

			if (is_numeric($entity_id)) {
				$entityId = $entity_id;
			} else {
				if (isset($data[$entity_id])) {
					$entityId = $data[$entity_id];
					if ($replace) {
						unset($data[$entity_id]);
						//TODO: remove from field list?
					}
				} else {
					Mage::throwException("{$entity_id} is not set inside export data");
					continue;
				}
			}

			if (isset($entityId))
			{
				$entity->load($entityId);

				foreach ($entity->getData() as $field => $value)
				{
					if ($map[$field])
						$field = $map[$field];
					if (!empty($prefix))
						$field = $prefix.$field;
					$data[$field] = $value;
				}

				$export->setBatchData($data)
					->save();
				$batch->parseFieldList($data);
			}
		}
		unset($id, $data, $entity);

		return $this;
	}
}
