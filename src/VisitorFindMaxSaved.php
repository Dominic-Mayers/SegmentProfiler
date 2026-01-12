<?php

namespace App;

use App\Traversal;

// We compute the saved nodes for each roots bottom-up. If the total saved nodes
// for the children is smaller than the saved nodes for the root, we temporarily
// mark the nodes as toBeMoved and visit the descendant to remove the toBeMoved
// marks below.  Otherwise, we set the saved nodes of the root to this total.
class VisitorFindMaxSaved extends AbstractVisitor {
   
        public function __construct(
            private   Traversal $traversal,
            TotalGraph $totalGraph, 
            $groupsWithNoInnerNodes = null
        ) {
            parent::__construct($totalGraph, $groupsWithNoInnerNodes);
        }
        

	public function afterChildren($currentId) {
            $adj = $this->totalGraph->adjActiveArrowsOut($currentId);
            $currentNode = $this->totalGraph->nodes[$currentId]; 
            $treeSize = 1;
            foreach($adj as $childId => $notused) {
                $treeSize += $this->totalGraph->nodes[$childId]->attributes['treeSize'];
            }
            $currentNode->attributes['treeSize'] = $treeSize;
            
            $groupSize = $currentNode->type === $this->totalGraph->treeType ? 1 : \count($currentNode->innerNodesId); 
            $currentSaved = ($treeSize - 1); 
            $childrenSaved = 0; 
            foreach ($adj as $childId => $notused) {
                $childrenSaved += $this->totalGraph->nodes[$childId]->attributes['saved']; 
            }
            $this->totalGraph->nodes[$currentId]->attributes['maxSaved'] = false;
            if ($currentSaved >= $childrenSaved) {
                $visitorSetMaxSavedFalse = new VisitorSetMaxSavedFalse($this->totalGraph);
                $this->traversal->visitNodes($visitorSetMaxSavedFalse, $currentId); 
                $this->totalGraph->nodes[$currentId]->attributes['maxSaved'] = true;
                $this->totalGraph->savedGroups[$currentNode->attributes['treeKeyWithEmpty']] ??= 0;
                $this->totalGraph->savedGroups[$currentNode->attributes['treeKeyWithEmpty']]++;
                $this->totalGraph->nodes[$currentId]->attributes['saved'] = $currentSaved;
            } else {
                $this->totalGraph->nodes[$currentId]->attributes['saved'] = $childrenSaved;
            }       
        }
}