<?php
include '../../bundles/Codeforweb/SegmentNotes/src/Note.php';

use Codeforweb\SegmentNOtes\Note; 


$s1 = Note::initNote('A');
Note::endNote($s1);

$s1 = Note::initNote('B');

    $s2 = Note::initNote('A');
        $s3 = Note::initNote('B');
            $s4 = Note::initNote('A');
            Note::endNote($s4);
        Note::endNote($s3);
    Note::endNote($s2);

    $s2 = Note::initNote('C');
        $s3 = Note::initNote('B');
            $s4 = Note::initNote('C');
            Note::endNote($s4);
        Note::endNote($s3);
    Note::endNote($s2);

Note::endNote($s1);

file_put_contents("/home/dominic/app_devel/SegmentProfiler/input/scnTest.profile", Note::$segmentNotes);
