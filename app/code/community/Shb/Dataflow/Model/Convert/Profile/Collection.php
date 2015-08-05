<?php

class Shb_Dataflow_Model_Convert_Profile_Collection
extends Mage_Core_Model_Convert
{
    /**
     * This improved `importProfileXml` method makes possibile to use
     * any action variable as a map, just putting several `map` elements
     * inside a `var` element.
     */
    public function importProfileXml($name)
    {
        if (!$this->_xml) {
            return $this;
        }
        $nodes = $this->_xml->xpath("//profile[@name='".$name."']");
        if (!$nodes) {
            return $this;
        }
        $profileNode = $nodes[0];

        $profile = $this->addProfile($name);
        $profile->setContainers($this->getContainers());
        foreach ($profileNode->action as $actionNode) {
            $action = $profile->addAction();
            foreach ($actionNode->attributes() as $key=>$value) {
                $action->setParam($key, (string)$value);
            }

            if ($actionNode['use']) {
                $container = $profile->getContainer((string)$actionNode['use']);
            } else {
                $action->setParam('class', $this->getClassNameByType((string)$actionNode['type']));
                $container = $action->getContainer();
            }
            $action->setContainer($container);
            if ($action->getParam('name')) {
                $this->addContainer($action->getParam('name'), $container);
            }

            $country = '';

            /** @var $varNode Varien_Simplexml_Element */
            foreach ($actionNode->var as $key => $varNode) {
                if (isset($varNode->map)
                and count($varNode->map)) {
                    $mapData = array();
                    foreach ($varNode->map as $mapNode) {
                        $mapData[(string)$mapNode['name']] = (string)$mapNode;
                    }
                    $container->setVar((string)$varNode['name'], $mapData);
                }  else {
                    $value = (string)$varNode;

                    /**
                     * Get state name from directory by iso name
                     * (only for US)
                     */
                    if ($value && 'filter/country' == (string)$varNode['name']) {
                        /**
                         * Save country for convert state iso to name (for US only)
                         */
                        $country = $value;
                    } elseif ($value && 'filter/region' == (string)$varNode['name'] && 'US' == $country) {
                        /**
                         * Get state name by iso for US
                         */
                        /** @var $region Mage_Directory_Model_Region */
                        $region = Mage::getModel('directory/region');

                        $state = $region->loadByCode($value, $country)->getDefaultName();
                        if ($state) {
                            $value = $state;
                        }
                    }

                    $container->setVar((string)$varNode['name'], $value);
                }
            }
        }

        return $this;
    }

}
