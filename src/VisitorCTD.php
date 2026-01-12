<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorCTD extends AbstractVisitorT {
    
        private array $groups;
        private bool|string $currentGroupKey;
        
        public function init() {
            if ( isset($this->groups) || isset($this->currentGroupKey) ) {
                echo "Error: VisitorCTD is not reentrant";
                exit();                 
            }
            $this->groups = []; 
            $this->currentGroupKey = null; 
        }
        
	public function beforeChildren($currentId) {
            if ($this->totalGraph->nodes[$currentId]->type === 'T' || 
                $this->totalGraph->nodes[$currentId]->type === 'CT') {
                if ($this->currentGroupKey === null) {
                    $this->groups[$currentId] = [];
                    $this->currentGroupKey = $currentId; 
                }
                $this->groups[$this->currentGroupKey][] = $currentId;
            }
	}
        
	public function afterChildren($currentId) {
            if ( isset($this->currentGroupKey) && $this->currentGroupKey === $currentId  ) {
                $this->currentGroupKey = null; 
            }
	}

	public function finalize () {
            foreach( $this->groups as $group) {
                if (count($group) > 1 ) {
                    $treeKey = $this->totalGraph->nodes[$group[0]]->attributes['treeKey'];
                    $treeLabel = $this->totalGraph->treeLabels[$treeKey];
                    $innerLabel = explode('.', $treeLabel)[0]; 
                    $groupRep = $this->totalGraph->nodes[$group[0]]; 
                                              
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'CTD', $group, $groupRep);
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['CTD']) ) {
                        // Very unlikely, but to cover all cases ...
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
            unset($this->groups);
            unset($this->currentGroupKey); 
        }
}