<?php
include '../../bundles/Codeforweb/SegmentNotes/src/Note.php';

use Codeforweb\SegmentNOtes\Note; 

for ( $i=0; $i<=1; $i++) {
    $s = Note::initNote('A');
    Note::endNote($s);

    $s = Note::initNote('B');
    $s1 = Note::initNote('A');
    Note::endNote($s1);

    $s1 = Note::initNote('B');
    Note::endNote($s1);

    $s1 = Note::initNote('A');
    Note::endNote($s1);

    $s1 = Note::initNote('C');
    Note::endNote($s1);
    
    $s1 = Note::initNote('B');
    Note::endNote($s1);

    Note::endNote($s);

    $s = Note::initNote('C');
    Note::endNote($s);

    $s = Note::initNote('A');
    Note::endNote($s);

    $s = Note::initNote('B');
    Note::endNote($s);

    $s = Note::initNote('C');
    Note::endNote($s);
}

file_put_contents("/home/dominic/app_devel/SegmentProfiler/input/snTest.profile", Note::$segmentNotes);
