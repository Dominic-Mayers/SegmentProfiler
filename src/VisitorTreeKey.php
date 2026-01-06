<?php

namespace App;

class VisitorTreeKey extends AbstractVisitorT {


        public function init() {
            $this->totalGraph->treeLabelsTranspose = [];
            $this->totalGraph->treeLabels = []; 
        }
        
        public function afterChildren($currentId) {
            [$treeKey, $treeLabel] =  $this->setNewTree($currentId);
        }
}