<?php 
/*
 * It is a recursion on $topTreeSize and $level. For a tree, $topTreeSize is the
 * number of nodes in the tree less the total size of the forest subtrees. Fpr a
 * forest, $level is the number of times generateFprest must call itself to get
 * that forest. The empty forest has level 0. When generateForest calls itself 
 * to generate a subforest, $level reduces. In the base case, $level = 0, it
 * retuns the empty forest [] without recursively calling itself to generate a
 * subforest. GenerateForest is never called thereafter. When it calls 
 * createRootOverSubforest, it only passes the subforest and $topTreeSize, not
 * the level. The forest is constant when it calls itself, but the value of
 * $topTreeSize reduces, because it gets split among $nbChildren. The base case
 * is either 0 or 1. When $topTreeSize is 1, it creates a leaf. When
 * $topTreeSize is 0 and the forest is not empty, it picks a tree at random in
 * the forest with no need to compute any children or to create any new node.
 * The case where topTreeSize is 0 and there is no forest requires the notion
 * of null tree.   
 * .
 * In  GenerateForest 
 */
function createRootNode($labels, $topTreeSize,  $child_range, $forestLevel, $forestSizePerLevel) {
    $subforest = generateForest ($labels, $topTreeSize,  $child_range, $forestLevel, $forestSizePerLevel);
    fwrite(STDERR, "Subforest root keys: "); 
    foreach($subforest as $node) {fwrite(STDERR, $node['key'] . "|");} 
    fwrite(STDERR, PHP_EOL); 
    return createRootOverSubforest($labels, $topTreeSize, $child_range, $subforest);
}

function createRootOverSubforest($labels, $topTreeSize, $child_range, $subforest) {
    //static $nodeId = 0;
    if ( $child_range[0] === 0 ) {
        fwrite(STDERR, "Error: the no children case is determined by the code. The range must start at 1 or higher.". PHP_EOL);
        exit(); 
    }
    if ( $child_range[1] === 1 ) {
        fwrite(STDERR, "Error: more than one child must be allowed in tne range.". PHP_EOL);
        exit(); 
    }
    if (empty($labels)) {
        fwrite(STDERR, "Error: At the least one label is needed." . PHP_EOL);
        exit();
    }
    if ($topTreeSize === 0 && !empty($subforest)) {
        $k = mt_rand(0, \count($subforest) -1);
        $node = $subforest[$k];
        return $node; 
    }
    if ($topTreeSize === 0 && empty($subforest)) {
        return null;         
    }
    if ($topTreeSize === 1) {
        $node['label'] = 'L';
        $node['key'] = '';
        $node['children'] = []; 
        return $node;         
    }
    $node['label'] = $labels[mt_rand(0, \count($labels)-1)];
    $node['children'] = [];
    $nbChildren = mt_rand($child_range[0],$child_range[1]); 
    $num = starAndBar($nbChildren, $topTreeSize - 1); 
    foreach ( $num as $childTopTreeSize) {
        $root = createRootOverSubforest($labels, $childTopTreeSize, $child_range, $subforest);
        if ($root === null) {continue;}
        $node['children'][] =  $root; 
    }
    return $node;
}

function generateForest ($labels, $topTreeSize,  $child_range, $level, $forestSizePerLevel) {
    // Level n forest  = a provided level n-1 subforest + $sizePerLevel level n - 1 trees computed above that subforest. 
    // Level 0 forest  = empty array. 
    // So we need the code for level n - 1 trees computed above a n-1 subforest. 
    // The case $n = 1 (level 1 tree and forest) must be managed separately. 
    
    if ($level === 0) {return [];}
    $forest = $subforest = generateForest ($labels, $topTreeSize, $child_range, $level-1, $forestSizePerLevel); 
    for ($i=0; $i < $forestSizePerLevel; $i++) {
        $treeRoot = createRootOverSubforest($labels, $topTreeSize, $child_range, $subforest);
        if ($treeRoot === null) { continue; }
        $k = \count($forest); 
        $treeRoot['key']= $k;
        $treeRoot['label'] .= "($k)";
        $forest[] = $treeRoot; 
    }
    return $forest;     
}

function starAndBar($k, $n ) {
    if ($k === 1) {return [$n];} 
    for ($i = 0; $i < $k-1; $i++ ) { $l[$i] = mt_rand(0, $n); }
    sort($l);
    array_unshift($l, 0);
    $l[$k] = $n;         
    for ($i = 0; $i < $k; $i++ ) { $num[$i] = $l[$i+1] - $l[$i]; }
    return $num; 
}

function createProfile($node) {
    static $id= 0;
    static $fakeTime=0;
    $curId = $id++; 
    $curTime=$fakeTime;
    if ($curId!==0) { 
        $fakeTime += mt_rand(1,10000); 
        echo "$curId:startName={$node['label']}".PHP_EOL;
    }
    foreach ($node['children'] as $child) {
        createProfile($child); 
    }
    if ($curId!==0) { 
        $timeFct = $fakeTime-$curTime; 
        echo "$curId:timeFct=$timeFct". PHP_EOL; 
        echo "$curId:endName=none". PHP_EOL;
    }
}

$labels = ['A', 'B', 'C'];
$node = createRootNode($labels, 10,  [1,3], 2, 2); 
createProfile($node); 