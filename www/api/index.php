<?php


	error_reporting(E_ALL);
	ini_set('display_errors', 'On');


require 'vendor/autoload.php';



$app = new \Slim\Slim();

$app->get('/hello/:name', function ($name) {
    echo "Hello, $name";
});


$app->get('/', function () {
    

	include('html.html');

});


try{
	$db = new PDO('mysql:host=mysql;charset=utf8;dbname=transcripts', "root", "tiger");
	//debug messages
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
	die('Error Connecting to Database: ' . $e->getMessage());
}	

//API
 

//before we start we are going to need a lookup of the transcripts
	$query = $db->prepare(
		'SELECT 
			*

		FROM `transcripts`


		'
	);

	$transcripts = array();
	$transcriptsByMd5 = array();
	$query->execute();	
	while($row = $query->fetch(PDO::FETCH_OBJ)) {



		$transcripts[(string)$row->intervieweeURI] = $row;
		$transcriptsByMd5[(string)$row->md5] = $row;

	}


//PEOPLE



//build dump
$app->get('/people/all/build', function () {

	global $db;
    
	$query = $db->prepare(
		'SELECT 
			name, uri, image, coinInfo as `comment`

		FROM `authority`


		'
	);

	$query->execute();	

	$results = peopleProcessor($query);

	file_put_contents('dump/people.json',json_encode($results[0]));

	//the json encode function will quietly fail....so make sure it didn't
	if (json_last_error() != JSON_ERROR_NONE){
		die("Error outputing json file");
	}	
	file_put_contents('dump/people.nt',$results[1]);

});

$app->get('/people/all(/:nt/?)', function ($nt = false) use ($app) {

	if ($nt == false){

		$app->contentType('application/json');


		echo file_get_contents('dump/people.json');


	}else{

		$app->contentType('text/plain');



		echo file_get_contents('dump/people.nt');


	}



});







//person search
$app->get('/people/search/:name(/:nt/?)', function ($name, $nt = false) use ($app) {

	global $db;
    
	$query = $db->prepare(
		'SELECT 
			name, uri, image, coinInfo as `comment`

		FROM `authority`

		WHERE `name` LIKE :name

		


		'
	);

	$query->execute(array( 'name' => '%' . $name . '%'));	

	$results = peopleProcessor($query);


	if ($nt == false){

		$app->contentType('application/json');
		echo json_encode($results[0]);

		//the json encode function will quietly fail....so make sure it didn't
		if (json_last_error() != JSON_ERROR_NONE){
			die("Error outputing json file");
		}	

	}else{

		$app->contentType('text/plain');

		echo $results[1];
		


	}



});

$app->get('/relationships/all/build', function () use ($app) {
	

	global $db;
    
	$query = $db->prepare(
		'SELECT 
			name, uri, image, coinInfo as `comment`

		FROM `authority`


		'
	);


	$query->execute();

	$count = 0;

	file_put_contents('dump/relationships.nt', '');
	file_put_contents('dump/relationships.json', '');

	$allJson = array();

		$peopleLookup =  json_decode(file_get_contents('dump/people.json'));


	$peopleLookupAry = array();
	$nodeLookupAry = array();	

	$count = 0;
	while($row = $query->fetch(PDO::FETCH_OBJ)) {


		$peopleLookupAry[$row->uri] = $row->name;
		$nodeLookupAry[$row->uri] = $count;

		$r = loadRelationships($row->uri);

		$aJson = new stdClass;
		$aJson->uri = $row->uri;
		$aJson->uriEncoded = urlencode($row->uri);
		$aJson->relationships = array();

		file_put_contents('dump/relationships.nt', $r[1], FILE_APPEND);
		
		foreach ($r[0] as $a){
			$aJson->relationships[] = $a;
		}
	
		$allJson[] = $aJson;

		$count++;

	}

	file_put_contents('dump/relationships.json', json_encode($allJson));


	$edges = array();


	//build the edges
	foreach ($allJson as $key => $value) {

	
		foreach ($value->relationships as $r){

			$aRep = new stdClass;
			$value->uri = str_replace('%2F','/',$value->uri);
			$r->uri = str_replace('%2F','/',$r->uri);

			if ($r->isTalkingAbout){
				
				
				$aRep->source = $nodeLookupAry[$value->uri];
				//echo $r->uri	
				$aRep->target = $nodeLookupAry[$r->uri]; 

			}else{

				//print $r->uri;
				$aRep->target = $nodeLookupAry[$value->uri];
				$aRep->source = $nodeLookupAry[$r->uri]; 

			}

			$edges[] = $aRep;
		}


	}



	//build it
	@date_default_timezone_set("GMT"); 

	$writer = new XMLWriter(); 

	$writer->openURI('dump/all.gexf');
	$writer->startDocument('1.0');

	$writer->setIndent(4); 

	$writer->startElement('gexf'); 
	$writer->writeAttribute('version', '1.2'); 
	$writer->writeAttribute('xmlns', 'http://www.gexf.net/1.2draft'); 


		$writer->startElement("graph"); 
		$writer->writeAttribute('mode', 'static'); 
		$writer->writeAttribute('defaultedgetype', 'directed'); 
			
			//add the nodes
			$writer->startElement("nodes"); 
				
				
				
				foreach ($nodeLookupAry as $key => $value){
					
					$writer->startElement("node"); 
					$writer->writeAttribute('id', (string) $value);
					$writer->writeAttribute('label', (string) utf8_encode(htmlentities($peopleLookupAry[$key], ENT_QUOTES)));
					$writer->endElement(); 
				
					 
				}
				
				
			//nodes
			$writer->endElement(); 
			
			//add the edges
			$writer->startElement("edges"); 
				
				
			
				foreach ($edges as $edge){
					
					
					$writer->startElement("edge"); 
					$writer->writeAttribute('source', (string) $edge->source);
					$writer->writeAttribute('target', (string) $edge->target);
					//$writer->writeAttribute('label', (string) $edge->label);
					$writer->endElement(); 
					 
				}
				
				
			//edges
			$writer->endElement(); 
			
								
		
		//graph
		$writer->endElement(); 
		
	//main	
	$writer->endElement(); 

	$writer->endDocument(); 

	$writer->flush(); 


});



$app->get('/relationships/all(/:nt/?)', function ($nt = false) use ($app) {

	if ($nt == "gexf"){


		$response = $app->response();
		$response['Content-Type'] = 'application/gexf+xml;charset=utf-8'; 
		$response->status(200);
		
		header('Content-type: application/gexf+xml;charset=utf-8');
		header('Content-Disposition: attachment; filename="LinkedJazz knowOf Network.gexf"');
		
		echo file_get_contents('dump/all.gexf');

		die();

	}


	if ($nt == false){
		$app->contentType('application/json');
		echo file_get_contents('dump/relationships.json');
	}else{
		$app->contentType('text/plain');
		echo file_get_contents('dump/relationships.nt');
	}
});



$app->get('/relationships/ego/:uri(/:nt/?)', function ($uri, $nt=false) use ($app) {

	$results = loadRelationships($uri);

	$resultsAll = $results[0];

	$triples = $results[1];
	

	//now loop through an load the the relationhips for each one they are connected to
	$finalJson = new stdClass();

	$finalJson->ego = $results[0];

	$finalJson->firstDegree = array();


	

	foreach ($results[0] as $a){


		$resultsChild = loadRelationships($a->uri);

		$triples .= $resultsChild[1];
		
		$firstDegreeObj = new stdClass();
		$firstDegreeArry = array();


		foreach ($resultsChild[0] as $b){

			$firstDegreeArry[] = $b;


		}

		$firstDegreeObj->uri = $a->uri;
		$firstDegreeObj->uriEncoded = urlencode($a->uri);
		$firstDegreeObj->relationships = $firstDegreeArry;

		$finalJson->firstDegree[] =  $firstDegreeObj;

	}

	

	if ($nt == 'gexf'){

		$peopleLookup =  json_decode(file_get_contents('dump/people.json'));

		$peopleLookupAry = array();
		foreach($peopleLookup as $p){

			$peopleLookupAry[$p->uri] = $p->name;

		}


		//build the node lookup 
		$nodeLookup = array();

		//first get the core relationships
		$nodeLookup[$uri] = 0; 

		$count = 1;
		foreach ($finalJson->ego as $e){

			$nodeLookup[$e->uri] = $count; 

			$count++;

		}


		//now all the first degree
		foreach ($finalJson->firstDegree as $r){

			if (!isset($nodeLookup[$r->uri])){

				$nodeLookup[$r->uri] = $count; 
				$count++;				
			}

			foreach ($r->relationships as $rr){

				if (!isset($nodeLookup[$rr->uri])){

					$nodeLookup[$rr->uri] = $count; 
					$count++;				
				}

			}

		}

		//build the realtionships
		$edges = array();

		foreach ($finalJson->ego as $e){

			$aRep = new stdClass;

			if ($e->isTalkingAbout){
				
				$aRep->source = $nodeLookup[$uri];
				$aRep->target = $nodeLookup[$e->uri]; 

			}else{
				$aRep->target = $nodeLookup[$uri];
				$aRep->source = $nodeLookup[$e->uri]; 

			}
			

			$edges[] = $aRep;

		}		

		foreach ($finalJson->firstDegree as $r){


			foreach ($r->relationships as $rr){

				$aRep = new stdClass;

				if ($rr->isTalkingAbout){
					
					$aRep->source = $nodeLookup[$r->uri];
					$aRep->target = $nodeLookup[$rr->uri]; 

				}else{
					$aRep->target = $nodeLookup[$r->uri];
					$aRep->source = $nodeLookup[$rr->uri]; 

				}

				$edges[] = $aRep;


			}

		}


		//print_r($edges);

		$response = $app->response();
		$response['Content-Type'] = 'application/gexf+xml;charset=utf-8'; 
		$response->status(200);

		$uri = str_replace('%2F','/',$uri);
		
		header('Content-type: application/gexf+xml;charset=utf-8');
		header('Content-Disposition: attachment; filename="LinkedJazz Network ' . $peopleLookupAry[$uri] . '.gexf"');
		

		@date_default_timezone_set("GMT"); 
		
		$writer = new XMLWriter(); 

		$writer->openURI('php://output'); 
		$writer->startDocument('1.0');
		
		$writer->setIndent(4); 

		$writer->startElement('gexf'); 
		$writer->writeAttribute('version', '1.2'); 
		$writer->writeAttribute('xmlns', 'http://www.gexf.net/1.2draft'); 


			$writer->startElement("graph"); 
			$writer->writeAttribute('mode', 'static'); 
			$writer->writeAttribute('defaultedgetype', 'directed'); 
				
				//add the nodes
				$writer->startElement("nodes"); 
					
					
					
					foreach ($nodeLookup as $key => $value){
						
						if (array_key_exists($key, $peopleLookupAry)) {

							$writer->startElement("node"); 
							$writer->writeAttribute('id', (string) $value);
							$writer->writeAttribute('label', (string) utf8_encode(htmlentities($peopleLookupAry[$key], ENT_QUOTES)));
							$writer->endElement(); 
						}
						 
					}
					
					
				//nodes
				$writer->endElement(); 
				
				//add the edges
				$writer->startElement("edges"); 
					
					
				
					foreach ($edges as $edge){
						
						
						$writer->startElement("edge"); 
						$writer->writeAttribute('source', (string) $edge->source);
						$writer->writeAttribute('target', (string) $edge->target);
						//$writer->writeAttribute('label', (string) $edge->label);
						$writer->endElement(); 
						 
					}
					
					
				//edges
				$writer->endElement(); 
				
									
			
			//graph
			$writer->endElement(); 
			
		//main	
		$writer->endElement(); 
		
		$writer->endDocument(); 
		
		$writer->flush(); 


	

		die();

	}

	

	
	if ($nt == false){
		$app->contentType('application/json');
		echo json_encode($finalJson);
		//the json encode function will quietly fail....so make sure it didn't
		if (json_last_error() != JSON_ERROR_NONE){
			die("Error outputing json file");
		}	
	}else{
		$app->contentType('text/plain');
		echo $triples;
	}


});



//realtionship, return all of the occurances of a relationship and the user input on that relationship
$app->get('/relationships/:uri(/:nt/?)', function ($uri, $nt=false) use ($app) {
	$results = loadRelationships($uri);
	if ($nt == false){
		$app->contentType('application/json');
		echo json_encode($results[0]);
		//the json encode function will quietly fail....so make sure it didn't
		if (json_last_error() != JSON_ERROR_NONE){
			die("Error outputing json file");
		}	
	}else{
		$app->contentType('text/plain');
		echo $results[1];
	}
});


//realtionship, return all of the occurances of a relationship and the user input on that relationship
$app->get('/compare/:uri/:uri2', function ($uri, $uri2) use ($app) {

	$results = loadRelationships($uri);

	foreach ($results[0] as $result) {

		if ($result->uriEncoded == urlencode($uri2)){

			$app->contentType('application/json');

			echo json_encode($result);

			//the json encode function will quietly fail....so make sure it didn't
			if (json_last_error() != JSON_ERROR_NONE){
				die("Error outputing json file");
			}	

			
		}



	}



	
	// 
	// if ($nt == false){
	// 	$app->contentType('application/json');
	// 	echo json_encode($results[0]);
	// 	//the json encode function will quietly fail....so make sure it didn't
	// 	if (json_last_error() != JSON_ERROR_NONE){
	// 		die("Error outputing json file");
	// 	}	
	// }else{
	// 	$app->contentType('text/plain');
	// 	echo $results[1];
	// }
});

$app->get('/text/:transcript', function ($transcript) use ($app) {

	global $db;


	$query = $db->prepare(
	'SELECT 
		*

	FROM `transcripts`

	WHERE `md5` = :transcript

	'
	);

	$query->execute(array( 'transcript' => $transcript));	

	$allText = array();

	while($row = $query->fetch(PDO::FETCH_OBJ)) {

		$allText[] = $row;

	}


	if (count($allText) > 0){
		$allText = $allText[0];
	}

	//print_r($allText);

	$app->contentType('application/json');
	echo json_encode($allText);
	//the json encode function will quietly fail....so make sure it didn't
	if (json_last_error() != JSON_ERROR_NONE){
		die("Error outputing json file");
	}	

});

$app->get('/text/:transcript/:ids', function ($transcript, $ids) use ($app) {

	global $db;

	//$idsSlpit = explode(",",$ids);
	//$ids = mysqli_real_escape_string($ids);

	$query = $db->prepare(
	'SELECT 
		transcript, text, idLocal, type, speaker

	FROM `text`

	WHERE `transcript` = :transcript and `idLocal` IN(' . $ids . ')

	'
	);

	$query->execute(array( 'transcript' => $transcript ));	

	$allText = array();

	while($row = $query->fetch(PDO::FETCH_OBJ)) {

		$allText[] = $row;

	}

	//print_r($allText);

	$app->contentType('application/json');
	echo json_encode($allText);
	//the json encode function will quietly fail....so make sure it didn't
	if (json_last_error() != JSON_ERROR_NONE){
		die("Error outputing json file");
	}	

});





function loadRelationships($uri){
	
	$uri = str_replace('%2F','/',$uri);

	global $transcripts, $db;

	if (isset($transcripts[$uri])){

		$query = $db->prepare(
		'SELECT 
			transcript, idLocal, type, personURI as `uri`

		FROM `matches`

		WHERE `personURI` = :uri OR transcript = :md5

		'
		);

		$query->execute(array( 'uri' => $uri, 'md5' => $transcripts[$uri]->md5));	


	}else{


		$query = $db->prepare(
		'SELECT 
			transcript, idLocal, type, personURI as `uri`

		FROM `matches`

		WHERE `personURI` = :uri

		'
		);

		$query->execute(array( 'uri' => $uri));	





	}

	//print urlencode('<http://dbpedia.org/resource/Sam_Rivers>');

	//echo $uri;

	return relationshipsProcessor($query, $uri);






}








function relationshipsProcessor($query, $uri){

	global $transcriptsByMd5, $transcripts, $db;

	$allJson = Array();
	$allTriples = '';

	while($row = $query->fetch(PDO::FETCH_OBJ)) {


		//if the URI is of themself, it means the other transcript referenced this perison, so switch it around
		if ($row->uri == $uri){

			//print $row->transcript;
			//print_r($transcriptsByMd5[$row->transcript]);

			$row->uri = $transcriptsByMd5[$row->transcript]->intervieweeURI;



		}



		//is this realtionship already know about?

		$add = true;
		foreach ($allJson as $aRelationship) {
		    if ($aRelationship->uri == $row->uri) {

		        $add = false;
		        $aRelationship->count = $aRelationship->count + 1;

				$occurances = new stdClass();
				$occurances->id = $row->idLocal;
				$occurances->type = $row->type;

				$addOccurance = true;
				foreach ($aRelationship->occurances as $aOccurance) {
		    		if ($aOccurance->id == $row->idLocal && $aOccurance->type == $row->type ) {		
		    			$addOccurance = false;
		    		}
		    	}		

		    	if ($addOccurance){
					$aRelationship->occurances[] = $occurances;
				}

		        //break;
		    }
		}

		if ($add){		

			$resultObj = new stdClass();
			$resultObj->transcript = $row->transcript;
			$resultObj->uri = $row->uri;
			$resultObj->uriEncoded = urlencode($row->uri);		
			$resultObj->count = 1;


			$occurances = new stdClass();

			$occurances->id = $row->idLocal;
			$occurances->type = $row->type;

			$resultObj->occurances = array($occurances);


			if (isset($transcripts[$uri])){
				if ($transcripts[$uri]->md5 == $row->transcript){
					$allTriples = $allTriples . 
					$uri . ' <http://purl.org/vocab/relationship/knowsOf> ' . $row->uri . " .\n";

					
					$resultObj->isTalkingAbout = true;
				}else{
					$allTriples = $allTriples . 
					$row->uri . ' <http://purl.org/vocab/relationship/knowsOf> ' . $uri . " .\n";

					$resultObj->isTalkingAbout = false;

				}
			}else{

					$allTriples = $allTriples . 
					$row->uri . ' <http://purl.org/vocab/relationship/knowsOf> ' . $uri . " .\n";
					
					$resultObj->isTalkingAbout = false;



			}
			


			//find out the CS relationships people said about this relationship
			$cs = $db->prepare(
			'SELECT 
				id, source, target, transcript, value, idLocals

			FROM `cs_results`

			WHERE (`source` = :p1 AND `target` = :p2) OR  (`source` = :p2 AND `target` = :p1)  

			'
			);


			$resultObj->userTalkingAbout = array();
			$resultObj->userBeingTalkedAbout = array();

			$cs->execute(array( 'p1' => $uri, 'p2' => $row->uri));	

			//print $uri . " ~ " .  $row->uri;

			while($csRow = $cs->fetch(PDO::FETCH_OBJ)) {

				$csRow->sourceEncoded = urlencode($csRow->source);
				$csRow->targetEncoded = urlencode($csRow->target);	

				//print_r($row);
				if ($csRow->value == 'skip'){continue;}

				//$uri is talking about $row-uri influnce on themselfs
				if ($csRow->source == $uri){

					//print $csRow->source . " >> " . $csRow->target;
					//print_r($csRow);
					$csRow->value = returnFullNamespace($csRow->value);
					$resultObj->userTalkingAbout[] = $csRow;

					$allTriples = $allTriples . 
					$csRow->source . ' ' . $csRow->value . ' ' . $csRow->target . " .\n";

				//someone else is talking about $row-uri influnce on them
				}else{

					//print $csRow->source . " >> " . $uri;
					//print_r($csRow);
					$csRow->value = returnFullNamespace($csRow->value);
					$resultObj->userBeingTalkedAbout[] = $csRow;
					$allTriples = $allTriples . 
					$csRow->source . ' ' . $csRow->value . ' ' . $uri . " .\n";

				}

				



			}

			//print_r($resultObj);
			//print $allTriples;


			$allJson[]=$resultObj;

		}





	}


	return Array($allJson,$allTriples);



}







function returnFullNamespace($short){


	switch ($short) {
	    case 'rel:influenced_by':
	        return '<http://purl.org/vocab/relationship/influencedBy>';
	        break;
	    case 'rel:mentor_of':
	        return '<http://purl.org/vocab/relationship/mentorOf>';
	        break;
	    case 'mo:collaborated_with':
	        return '<http://purl.org/ontology/mo/collaborated_with>';
	        break;	        
	    case 'foaf:knows':
	        return '<http://purl.org/vocab/relationship/knowsOf>';
	        break;
	    case 'rel:acquaintance_of':
	        return '<http://purl.org/vocab/relationship/acquaintanceOf>';
	        break;
	    case 'rel:closeFriendOf':
	    	return '<http://purl.org/vocab/relationship/closeFriendOf>';
	    	break;
	    case 'rel:has_met':
	        return '<http://purl.org/vocab/relationship/hasMet>';
	        break;
	    case 'rel:friend_of':
	        return '<http://purl.org/vocab/relationship/friendOf>';
	        break;
	    case 'mo:collaborated_with_in_band_together':
	        return '<http://linkedjazz.org/ontology/inBandTogether>';
	        break;
	    case 'mo:collaborated_with_played_together':
	        return '<http://linkedjazz.org/ontology/playedTogether>';
	        break;	        
	    case 'mo:collaborated_with_was_bandmember':
	        return '<http://linkedjazz.org/ontology/bandmember>';
	        break;
	    case 'mo:collaborated_with_toured_with':
	        return '<http://linkedjazz.org/ontology/touredWith>';
	        break;

	    case 'mo:collaborated_with_was_bandleader':
	        return '<http://linkedjazz.org/ontology/bandLeaderOf>';
	        break;      	        	
	}


}




function peopleProcessor($query){





	$allResults = Array();
	$allTriples = '';

	while($row = $query->fetch(PDO::FETCH_OBJ)) {



		$resultObj = new stdClass();
		$resultObj->name = $row->name;
		$resultObj->uri = $row->uri;
		$resultObj->uriEncoded = urlencode($row->uri);	
		if ($row->image != ''){
			$resultObj->image = 'http://linkedjazz.org/image/square/' . $row->image;
		}		
		if ($row->comment != ''){
			$resultObj->comment = $row->comment;
		}

		$allResults[]=$resultObj;


		//build the triple form too
		
		//name
		$allTriples = $allTriples . 
		$row->uri . ' <http://xmlns.com/foaf/0.1/name> "' . htmlentities($row->name, ENT_QUOTES) . '"@en .' . "\n";

		//image
		if ($row->image != ''){
			$allTriples = $allTriples . 
			$row->uri . ' <http://dbpedia.org/ontology/thumbnail> <http://linkedjazz.org/image/square/' . $row->image . '> .' . "\n";
		}

		//comment
		if ($row->comment != ''){
			$allTriples = $allTriples . 
			$row->uri . ' <http://www.w3.org/TR/rdf-schema#rdfs:comment> "' . htmlentities($row->comment) . '"@en .' . "\n";
		}

	}


	return Array($allResults,$allTriples);



}
















$app->run();


?>
