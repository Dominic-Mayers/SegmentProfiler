<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorCTD extends AbstractVisitorT {
    
        private array $groups;
        private ?string $currentGroupKey;

        public function __construct ( private $groupsWithNoInnerNodes = null) {            
        }
        
        public function init() {
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
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'CTD', $group, $treeKey);
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['CTD']) ) {
                        // Very unlikely, but to cover all cases ...
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
        }
}