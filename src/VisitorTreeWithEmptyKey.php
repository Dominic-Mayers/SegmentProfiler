<?php

namespace App;

class VisitorTreeWithEmptyKey extends AbstractVisitorT {


        public function init() {
            $this->totalGraph->treeWithEmptyLabelsTranspose = [];
            $this->totalGraph->treeWithEmptyLabels = []; 
        }
        
        public function afterChildren($currentId) {
            $this->setNewTreeWithEmpty($currentId);
        }
}