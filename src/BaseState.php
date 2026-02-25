<?php
namespace App; 

class BaseState {

    /** @var array nodeId => nodeData */
    public array $nodes = [];

    /** @var array sourceId => [targetId => edgeData] */
    public array $arrowsOut = [];

    /** @var array targetId => [sourceId => edgeData] */
    public array $arrowsIn = [];

    /**
     * Apply a graph transformation
     * 
     * @param array $addNodes nodeId => nodeData
     * @param array $addAdjacency sourceId => [targetId => edgeData]
     * @param array $deleteNodes array of nodeIds to remove
     * @param array $options ['incremental' => bool]
     */
    public function applyGraphTransformation(
        array $addNodes = [],
        array $addAdjacency = [],
        array $deleteNodes = [],
        array $options = []
    ): void {
        $incremental = $options['incremental'] ?? false;

        // ---- 1. Delete nodes ----
        foreach ($deleteNodes as $nodeId) {
            unset($this->nodes[$nodeId]);
            unset($this->arrowsOut[$nodeId]);
        }

        // Remove edges pointing to deleted nodes
        foreach ($this->arrowsOut as $src => &$targets) {
            foreach ($deleteNodes as $tgt) {
                unset($targets[$tgt]);
            }
        }
        unset($targets); // break reference

        // ---- 2. Add nodes ----
        foreach ($addNodes as $nodeId => $nodeData) {
            $this->nodes[$nodeId] = $nodeData;
        }

        // ---- 3. Add adjacency ----
        foreach ($addAdjacency as $src => $targets) {
            if (!isset($this->arrowsOut[$src])) {
                $this->arrowsOut[$src] = [];
            }
            foreach ($targets as $tgt => $edgeData) {
                $this->arrowsOut[$src][$tgt] = $edgeData;
            }
        }

        // ---- 4. Update arrowsIn ----
        if ($incremental) {
            $this->updateIncomingIncremental($addNodes, $addAdjacency, $deleteNodes);
        } else {
            $this->rebuildIncoming();
        }
    }

    /**
     * Fully rebuild arrowsIn from arrowsOut
     */
    private function rebuildIncoming(): void {
        $incoming = [];
        foreach ($this->arrowsOut as $src => $targets) {
            foreach ($targets as $tgt => $edgeData) {
                if (!isset($incoming[$tgt])) {
                    $incoming[$tgt] = [];
                }
                $incoming[$tgt][$src] = $edgeData;
            }
        }
        $this->arrowsIn = $incoming;
    }

    /**
     * Incrementally update arrowsIn
     */
    private function updateIncomingIncremental(array $addNodes = [], array $addAdjacency = [], array $deleteNodes = []): void {
        // 1. Remove deleted nodes from arrowsIn
        foreach ($deleteNodes as $nodeId) {
            unset($this->arrowsIn[$nodeId]);
        }

        foreach ($this->arrowsIn as &$sources) {
            foreach ($deleteNodes as $tgt) {
                unset($sources[$tgt]);
            }
        }
        unset($sources); // break reference

        // 2. Initialize arrowsIn entries for new nodes
        foreach ($addNodes as $nodeId => $_) {
            if (!isset($this->arrowsIn[$nodeId])) {
                $this->arrowsIn[$nodeId] = [];
            }
        }

        // 3. Add new edges
        foreach ($addAdjacency as $src => $targets) {
            foreach ($targets as $tgt => $edgeData) {
                if (!isset($this->arrowsIn[$tgt])) {
                    $this->arrowsIn[$tgt] = [];
                }
                $this->arrowsIn[$tgt][$src] = $edgeData;
            }
        }
    }
}