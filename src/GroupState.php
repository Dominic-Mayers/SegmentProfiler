<?php
namespace App;

use App\BaseState; 

class GroupState  {
    
    public function __construct(private BaseState $baseState) {
    }
    
    public function adjActiveArrowsOut($sourceId) {
    // This is the same as using the active graph in its original definition, just
    // after the creation of the existing groups, but not after group desactivations.
        $adjNotInnerOut = [];
        $adjAllOut = $this->baseState->arrowsOut[$sourceId] ?? [];
        foreach ($adjAllOut as $targetId => $arrow) {
            if ( empty($this->baseState->nodes[$targetId]['groupId']) ) {
                $adjNotInnerOut[$targetId] = $arrow;
            }
        }
        return $adjNotInnerOut;
    }
        
    public function adjActiveArrowsIn($targetId) {
    // This is the same as using the active graph in its original definition, just
    // after the creation of the existing groups, but not after group desactivations.
        $adjNotInnerIn = [];
        $adjAllIn = $this->baseState->arrowsIn[$targetId] ?? [];
        foreach ($adjAllIn as $sourceId => $arrow) {
            if ( empty($this->baseState->nodes[$sourceId]['groupId']) ) {
	        $adjNotInnerIn[$sourceId] = $arrow;
            }
        }
        return $adjNotInnerIn;
    }
    
    public function incomingActiveOrder($nodeId) : int {
       return \count($this->adjActiveArrowsIn($nodeId)); 
    }

    public function addGroup($innerLabel, $type, $innerNodesId, $rootRep = null) {
        if ( count($innerNodesId) == 1)  {
            echo "Error: attempting to create a singleton".PHP_EOL; 
            exit();  
        }
        $groupId = $this->baseState->addNode($type); 
        //echo "Adding group $groupId." . PHP_EOL; 
        if ( isset($rootRep) ) {
            $this->baseState->nodes[$groupId]['attributes']['treeKey'] = $rootRep['attributes']['treeKey'];
            $this->baseState->nodes[$groupId]['attributes']['treeKeyWithEmpty'] = $rootRep['attributes']['treeKeyWithEmpty'];
        }
        $this->baseState->nodes[$groupId]['attributes']['innerLabel'] = $innerLabel;
        $this->baseState->nodes[$groupId]['attributes']['timeExclusive'] = 0; 
        foreach ($innerNodesId as $innerNodeId) {
            $this->baseState->nodes[$innerNodeId]['groupId'] = $groupId;
            $this->baseState->nodes[$groupId]['innerNodesId'][] = $innerNodeId;
            $this->baseState->nodes[$groupId]['attributes']['timeExclusive'] += 
            $this->baseState->nodes[$innerNodeId]['attributes']['timeExclusive'];
            //echo "Set groupId of $innerNodeId to $groupId and time attributes of that group.". PHP_EOL; 
        }
        //echo "Added group $groupId.". PHP_EOL; 
        return $groupId;
    }

    public function createGroup($groupId) {

        $innerNodesId = $this->baseState->nodes[$groupId]['innerNodesId'];
        $this->baseState->nodes[$groupId]['attributes']['timeInclusive'] = 0;
        $groupTimeInclusive = & $this->baseState->nodes[$groupId]['attributes']['timeInclusive']; 
        foreach ($innerNodesId as $nodeId) {
            $arrowsOut = $this->baseState->arrowsOut[$nodeId] ?? [];
            foreach ($arrowsOut as $targetId => $arrowOut) {
                $this->baseState->arrowsOut[$groupId][$targetId] ??= $this->baseState->createArrow($groupId, $targetId, 0);
                $this->baseState->arrowsOut[$groupId][$targetId]['calls'] += $arrowOut['calls'];
                //echo "Adding {$arrowOut['calls']} outgoing arrow from added $groupId" . ; 
                //" to $targetId because of its inner node $nodeId<br>".PHP_EOL;
                $this->baseState->arrowsIn[$targetId][$groupId] = $this->baseState->arrowsOut[$groupId][$targetId];
            }
            $arrowsIn = $this->baseState->arrowsIn[$nodeId] ?? [];
            foreach ($arrowsIn as $sourceId => $arrowIn) {
                $this->baseState->arrowsOut[$sourceId][$groupId] ??= $this->baseState->createArrow($sourceId, $groupId, 0);
                $this->baseState->arrowsOut[$sourceId][$groupId]['calls'] += $arrowIn['calls'];
                //echo "Adding {$arrowIn['calls']} incoming arrow from $sourceId 
                //to added $groupId because of its inner node $nodeId<br>".PHP_EOL;
                $timeInc = $arrowIn['timeInclusive'];
                $this->baseState->arrowsOut[$sourceId][$groupId]['timeInclusive'] += $timeInc;
                if (!in_array($sourceId, $innerNodesId)) {
                    // Here we have true incoming arrow from $sourceId toward $groupId.
                    $groupTimeInclusive += $timeInc;
                }
                $this->baseState->arrowsIn[$groupId][$sourceId] = $this->baseState->arrowsOut[$sourceId][$groupId];
            }
        }
    }

    public function removeNode($nodeId) {
        if (isset($this->arrowsIn[$nodeId])) {
            foreach ($this->arrowsIn[$nodeId] as $sourceId => $arrow) {
                unset($this->arrowsOut[$sourceId][$nodeId]);
                unset($this->arrowsIn[$nodeId][$sourceId]);
            }
            if (empty($this->arrowsOut[$sourceId])) {
                unset($this->arrowsOut[$sourceId]);
            }
            if (empty($this->arrowsIn[$nodeId])) {
                unset($this->arrowsIn[$nodeId]);
            }
        }
	if (isset($this->arrowsOut[$nodeId])) {
	    foreach ($this->arrowsOut[$nodeId] as $targetId => $arrow) {
                unset($this->arrowsOut[$nodeId][$targetId]);
                unset($this->arrowsIn[$targetId][$nodeId]);                                    
            }
            if (empty($this->arrowsOut[$nodeId])) {
                unset($this->arrowsOut[$nodeId]);
            }
            if (empty($this->arrowsIn[$targetId])) {
                unset($this->arrowsIn[$targetId]);
            }
        }
        $this->removeInnerNodes($nodeId);
        unset($this->nodes[$nodeId]);
    }

    public function removeInnerNodes($groupId) {
                
        if (empty($this->baseState->nodes[$groupId]) ) { return ; }
        $group = $this->baseState->nodes[$groupId]; 
        $innerNodesId = $group['innerNodesId']??[]; 
        foreach ($innerNodesId as $nodeId)
        {
            $this->removeNode($nodeId); 
            //echo "Removed node $nodeId.". PHP_EOL; 
        }
        $group['innerNodesId'] = [];
    }
}

