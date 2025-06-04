<?php
require_once ('vendor/autoload.php');

//include('src/Profiler.php');
$notesFile = new SplFileObject($argv[1]);
$profiler = new App\Profiler();
$profiler->getTree($notesFile);
$profiler->setExclusiveTime();
$profiler->setColorCode();
file_put_contents('output/tree.dot', $profiler->createGraph());

//$profiler->groupDescendentsPerName();
//$dot = $profiler->createDot([]);
//file_put_contents('output/dn.dot', $dot);

$profiler->fullGroupSiblingsPerName();
file_put_contents('output/fsn1.dot', $profiler->createGraph());

$profiler->groupSiblingsPerChildrenName();
file_put_contents('output/scn.dot', $profiler->createGraph());

$profiler->fullGroupSiblingsPerName();
file_put_contents('output/fsn2.dot', $profiler->createGraph());

$profiler->groupDescendentsPerName();
file_put_contents('output/dn.dot', $profiler->createGraph());

//$profiler->deactivateGroup('SCN00001');
file_put_contents('output/active.dot', $profiler->createGraph());

file_put_contents('output/sub.dot', $profiler->createGraph($profiler->getSubGraph('DN00001')));
pass();

function pass() {};
