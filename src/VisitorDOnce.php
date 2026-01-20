<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorDOnce extends AbstractVisitorT {
    
        private array $group;
        
        public function init() {
            if (isset ($this->group)) {
                echo "Error: VisitorDonce is not reentrant";
                exit(); 
            }
            $this->group = []; 
        }
        
	public function afterChildrenProcess($currentId) {

            $this->group[] = $currentId;
           
            //echo "Added $currentId in group $treeLabel.". PHP_EOL; 
	}

	public function finalize () {
                if (count($this->group) > 1 ) {
                    $treeKey = $this->totalGraph->nodes[$this->group[0]]->attributes['treeKey'];
                    $treeLabel = $this->totalGraph->treeLabels[$treeKey];
                    $innerLabel = explode('.', $treeLabel)[0];
                    $groupRep = $this->totalGraph->nodes[$group[0]]; 
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'D', $this->group, $groupRep);
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['D']) ) {
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL;                     
                }
                unset($this->group); 
        }
}