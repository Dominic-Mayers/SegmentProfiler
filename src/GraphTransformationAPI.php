<?php
namespace App;
//use Graphp\Graph\Graph; 
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\TotalGraph; 
use App\ActiveGraph; 
use App\Traversal; 
use App\VisitorDefaultActiveGraph; 
//use App\VisitorT;
//use App\VisitorTwe;
use App\VisitorCL;
//use App\VisitorCT;
//use App\VisitorCTwe;
use App\VisitorTreeKey;
use App\VisitorTreeWithEmptyKey;

class GraphTransformationAPI {
	
	public function __construct(
                private TotalGraph $totalGraph,
                private ActiveGraph $activeGraph, 
                private Traversal $traversal,
		private UrlGeneratorInterface $urlGenerator,
                private $groupsWithNoInnerNodes = null,
	) {
	}
	
        public function groupCL() {
                $visitorCL = new VisitorCL(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes($visitorCL);
	}

        public function groupCT() {
                $visitorCT = new VisitorCT(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes); 
		$this->traversal->visitNodes($visitorCT);
	}
        
        public function groupCTwe() {
		// For every non innernode, this only groups its non inner children with a same full name.
		$visitorCTwe = new VisitorCTwe(
                        $this->totalGraph,
                        $this->groupsWithNoInnerNodes);  
		$this->traversal->visitNodes($visitorCTwe);
	}

        //public function groupT() {
        //        $visitorT = new VisitorT(
        //                $this->totalGraph,
        //                $this->groupsWithNoInnerNodes); 
	//	$this->traversal->visitNodes( $visitorT);
	//}

	//public function groupTwe() {
        //        $visitorTwe = new VisitorTwe(
        //                $this->totalGraph,
        //                $this->groupsWithNoInnerNodes); 
	//	$this->traversal->visitNodes($visitorTwe);
	//}
       
	//public function groupTTD() {
        //        $visitorTTD = new VisitorTTD(
        //                $this->totalGraph,
        //                $this->groupsWithNoInnerNodes);
        //        $this->traversal->visitNodes($visitorTTD);                 
	//}        

	//public function groupTTweD() {
        //        $visitorTTweD = new VisitorTTweD(
        //                $this->totalGraph,
        //                $this->groupsWithNoInnerNodes);
        //        $this->traversal->visitNodes($visitorTTweD);                 
	//}        
        
        public function setTreeKey () {
                $visitorTreeKey = new VisitorTreeKey($this->totalGraph); 
                $this->traversal->visitNodes($visitorTreeKey); 
        }

        public function setTreeKeyWithEmpty () {
                $visitorTreeWithEmptyKey = new VisitorTreeWithEmptyKey($this->totalGraph); 
                $this->traversal->visitNodes($visitorTreeWithEmptyKey); 
        }

        public function contractionOnT () {
                $visitorContractionOnT = new VisitorContractionOnT($this->totalGraph); 
                $this->traversal->visitNodes($visitorContractionOnT); 
        }

        public function contractionOnTwe () {
                $visitorContractionOnTwe = new VisitorContractionOnTwe($this->totalGraph, 2 , 3); 
                $this->traversal->visitNodes($visitorContractionOnTwe); 
        }

        public function createDefaultActiveGraph () {
                // To be called once we have the totalGraph, after the groups have been created. 
                $visitorDefaultActiveGraph = new VisitorDefaultActiveGraph(
                        $this->totalGraph, 
                        $this->activeGraph);
                $this->traversal->visitNodes($visitorDefaultActiveGraph);
        }
}
