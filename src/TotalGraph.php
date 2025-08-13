<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

namespace App;

class TotalGraph {
        public string  $rootId = "00000"; 
        public array   $nodes = [];
        public array   $arrowsOut = []; 
        public array   $arrowsIn = [];
    
        public function getNotInnerArrowsOut($nodeId) {
        // This is the same as using the active graph in its original definition, just
        // after the creation of the existing groups, but not after group desactivations.
                $adjNotInnerOut = [];
                $adjAllOut = $this->arrowsOut[$nodeId] ?? [];
                foreach ($adjAllOut as $targetId => $arrow) {
                        if ( $this->nodes[$targetId]->groupId === null ) {
				$adjNotInnerOut[$targetId] = $arrow;
                        }
                }
                return $adjNotInnerOut;
        }
        
        public function getNotInnerArrowsIn($nodeId) {
        // This is the same as using the active graph in its original definition, just
        // after the creation of the existing groups, but not after group desactivations.
                $adjNotInnerIn = [];
                $adjAllIn = $this->arrowsIn[$nodeId] ?? [];
                foreach ($adjAllIn as $targetId => $arrow) {
                        if ( $this->nodes[$targetId]->groupId === null ) {
				$adjNotInnerIn[$targetId] = $arrow;
                        }
                }
                return $adjNotInnerIn;
        }
}