<?php

namespace App;

class VisitorSN extends Visitor {
    
        public bool $newNonSingletonSinceLastSet; 
    
	public function init() {
                $this->newNonSingletonSinceLastSet = false; 
		$this->groupsPhase1 = [];
                //echo "Starting SN".PHP_EOL."-----------".PHP_EOL;
	}

	public function beforeChildren($currentId) {
		$groups = [];
		$adjOut = $this->totalGraph->getNotInnerArrowsOut($currentId); 
                $type = "SN"; 
		foreach ($adjOut as $targetId => $arrow) {
                        $adjIn = $this->totalGraph->getNotInnerArrowsIn($targetId);
                        if (count($adjIn) > 1) {continue;}
			$label = $this->totalGraph->nodes[$targetId]->attributes['label'];
                        if (
                            $this->totalGraph->nodes[$targetId]->type !== "SN" && 
                            $this->totalGraph->nodes[$targetId]->type !== "DN" &&
                            $this->totalGraph->nodes[$targetId]->type !== "T" )
                        {
                            $type = "SNX";     
                        }
			$groups[$label][] = $targetId;
                        if (count($groups[$label]) == 2) {
                            //echo "Added node $targetId to a group with label $label, after {$groups[$label][0]}".PHP_EOL;
                        }
                        if (count($groups[$label]) > 2) {
                            //echo "Added node $targetId to a group with label $label".PHP_EOL;
                        }                        
		}
		foreach ($groups as $label => $group) {
                    if (count($group) > 1) {
			$this->groupsPhase1[] = $groupId = $this->addGroup($group, $type, $label);
                        $this->newNonSingletonSinceLastSet = true;                         
                        //echo "Added new group $groupId".PHP_EOL; 
                    }
		}
	}
        
        protected function createGroups() {

		foreach ($this->groupsPhase1 as $groupId) {
                        $group = $this->totalGraph->nodes[$groupId]; 
                        if (count($group->innerNodesId) <= 1  ) {continue;} 
			$this->createGroup($groupId);
                        if ($group->type === "SN") {
                            $this->removeInnerNodes($groupId); 
                        }
		}
	}        
}