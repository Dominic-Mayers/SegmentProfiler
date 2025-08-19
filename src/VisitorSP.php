<?php

namespace App;

class VisitorSP extends AbstractVisitorP {
                        
	public function init() {
                $this->paths = [];
                $this->pathsTranspose = [];
	}

	public function afterChildren($currentId) {
            
            [$key] = $this->setNewKeyPath($currentId);
                        
            // Now we create the children groups, just as in SN
            $groups = [];
            $adjOut = $this->totalGraph->getNotInnerArrowsOut($currentId); 
            $type = "SP"; 
            foreach ($adjOut as $targetId => $arrow) {
                    $key = $this->totalGraph->nodes[$targetId]->attributes['pathKey'];
                    $label = $this->paths[$key];
                    $groups[$label][] = $targetId;
                    //echo "Added nodeId $targetId in group $label" . PHP_EOL; 
            }
            foreach ($groups as $pathLabel => $group) {
                    if (count($group) > 1) {
                        $label = explode("\0", $pathLabel)[0]; 
                        $key = $this->pathsTranspose[$pathLabel]; 
			$this->groups[] = $groupId = $this->totalGraph->addGroup($label, $type, $group, $key);
                        $this->totalGraph->createGroup($groupId); 
                        $groupNode = $this->totalGraph->nodes[$groupId]; 
                        $this->totalGraph->removeInnerNodes($groupId); 
                        //echo "Completed group $groupId".PHP_EOL; 
                    }
            }            
	}
}