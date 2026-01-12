<?php

namespace App;

class VisitorTreeWithEmptyKey extends AbstractVisitorT {


        public function init() {
            $this->totalGraph->treeLabelsTransposeWithEmpty = [];
            $this->totalGraph->treeLabelsWithEmpty = []; 
        }
        
        public function afterChildren($currentId) {
            $this->setNewTreeWithEmpty($currentId);
        }
}