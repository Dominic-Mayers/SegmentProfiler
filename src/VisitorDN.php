<?php

namespace App;

class VisitorDN extends Visitor {
        // The methods beforeChildren and afterChildren only create the group
        // using addGroup: This includes setting the innerNodesId and groupId
        // of these inner nodes. It does not create any arrow.
        // This process is done in finalize

        // This is the array of groupId that have been identified and remains
        // to be created with addGroup (without the arrows).     
        private array $groupsPhase0; 
        private array $makeItDNXType;
        
        // Override parent method to remove inner nodes of DN groups.
        protected function createGroups() {

		foreach ($this->groupsPhase1 as $groupId) {
                        $group = $this->totalGraph->nodes[$groupId]; 
                        if (count($group->innerNodesId) === 1  ) {continue;} 
			$this->createGroup($groupId);
                        if ($group->type === "DN") {
                            $this->removeInnerNodes($groupId); 
                        }
		}
	}
        
	public function init() {
		$this->groupsPhase0 = [];
		$this->groupsPhase1 = [];
                $this->makeItDNXType = [];
                //echo "Starting DN".PHP_EOL."-----------".PHP_EOL;
	}

	public function beforeChildren($currentId) {
		$grps0 =& $this->groupsPhase0;
		$currentNode = $this->totalGraph->nodes[$currentId];
		$label = $currentNode->attributes['label'];
		$grps0[$label][] = $currentId;
                if (count ($grps0[$label]) == 2 ) {
                    //echo "Added node $currentId with label $label to a DN or DNX group, after {$grps0[$label][0]}".PHP_EOL;
                }
                if (count ($grps0[$label]) > 2 ) {
                    //echo "Added node $currentId with label $label to a DN or DNX group".PHP_EOL;
                }
                if (
                    $this->totalGraph->nodes[$currentId]->type !== "SN" && 
                    $this->totalGraph->nodes[$currentId]->type !== "DN" &&
                    $this->totalGraph->nodes[$currentId]->type !== "T" )
                {
                    $this->makeItDNXType[$label] = true;     
                }
	}

	public function afterChildren($currentId) {
		$grps0 = & $this->groupsPhase0;
		$grps1 = & $this->groupsPhase1;
		$currentNode = $this->totalGraph->nodes[$currentId];
		$label = $currentNode->attributes['label'];
		if (!isset($grps0[$label])) {
			return;
		}

		$firstInnerNodeId = $grps0[$label][0];
		if ($firstInnerNodeId === $currentId) {
			if (isset($grps0[$label][1])) {
                                $type = isset($this->makeItDNXType[$label]) && $this->makeItDNXType[$label] ? "DNX" : "DN"; 
 				$grps1[] = $groupId = $this->addGroup($grps0[$label], $type, $label); 
                                $a = 1;
                        }
			unset($grps0[$label]);
                        unset($this->makeItDNXType[$label]);
		}
	}

        public function finalize() {
            $this->createGroups();
        }

}