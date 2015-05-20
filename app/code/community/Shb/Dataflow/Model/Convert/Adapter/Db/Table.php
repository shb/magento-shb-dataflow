<?php

class Shb_Dataflow_Model_Convert_Adapter_Db_Table
extends Mage_Dataflow_Model_Convert_Adapter_Abstract
{
	public function load ()
	{
		return true;
	}

    public function save ()
    {
        $table = $this->getVar('table');
        if (empty($table)) {
            $this->addException("Table not defined", Mage_Dataflow_Model_Convert_Exception::FATAL);
            return $this;
        }
        $pk = $this->getVar('id');

		$to = $this->getAction()->getParam('to');
		$adapter = $this->getProfile()->getContainer($to);
        $db = $adapter->getResource();
        $columns = $db->describeTable($table);

        $batch = Mage::getSingleton('dataflow/batch');
        $export = $batch->getBatchExportModel();
        $ids = $export->getIdCollection();

        // Keep track of modified rows
        $updated = 0;
        $inserted = 0;
        foreach ($ids as $id)
        {
            $export->load($id);
            $data = $export->getBatchData();

            // Keep only table defined columns
            $row = array();
            foreach ($columns as $field => $meta)
                if (array_key_exists($field, $data))
                    $row[$field] = $data[$field];

            // Update or insert the rows into the table
            try
            {
                if (!empty($pk) and !empty($data[$pk]))
                {
						 // Look for existing row if id column is valorized
						 $where = $db->quoteInto($db->quoteIdentifier($pk).'=?', $data[$pk]);
                    $oldRow = $db->fetchAll('SELECT * FROM '.$db->quoteIdentifier($table).' WHERE '.$where);
					}
                if (isset($oldRow) and count($oldRow)) {
                    $updated += $db->update($table, $row, $where);
                } else {
                    $inserted += $db->insert($table, $row);
                }
            }
            catch (Exception $e)
            {
                $this->addException($e->getMessage(), Mage_Dataflow_Model_Convert_Exception::ERROR);
                continue;
            }
        }
        $this->addException("Rows updated: {$updated} / inserted: {$inserted}", Mage_Dataflow_Model_Convert_Exception::NOTICE);

        return $this;
    }

}
