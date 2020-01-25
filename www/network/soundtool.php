<?php


if (isset($_REQUEST['useId'])){

	header("content-type: application/json");
	
	$useId = $_REQUEST['useId'];

	if ($_REQUEST['append']=='true'){
		
		
		//not used for now
		
		
	}else{
		
		if (strpos($useId,"..")!==false){
			die("error");	
		}
		
		
		file_put_contents("img/" . $useId . ".meta", $_REQUEST['link']);
	
		echo "1";
	
		
	}

	

	die();

}


if (isset($_REQUEST['getVideos'])){

	header("content-type: text/plain");
	  
	if ($handle = opendir('img/')) {
		$jsFileNames='';
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				if (strpos($entry,'.meta')!==false){
					$jsFileNames .= $entry . ",";
				}
			}
			
			
		}
		$jsFileNames = substr($jsFileNames,0,strlen($jsFileNames)-1);
		echo $jsFileNames;
		closedir($handle);
	}
		

	die();

}
 


  
?>


<!DOCTYPE HTML>
<html>
<head>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="jquery.rdfquery.core.min-1.0.js"></script>
 
 

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Linked Jazz Network Audio Tool</title>

<style>

	body{
		font-family:Helvetica, Arial, sans-serif;
		
	}

	a{
		color:#000;
	}

	#left{
		background-color:#F7F7F7;
		width:30%;
		min-width:30%;
		float:left;
		overflow:auto;
		
		
		
	}
	#right{
		float:left;
		width:65%;
		background-color:#CCC; 
	}
	#left span{
		padding:10px;
		
	}

	.noImageYet, .hasImage{margin:5px;}
	.noImageYet{background-color:#FFD2D2;}
	.hasImage{background-color:#C1E0FF;}	

	.nameDiv a{
		font-size:10px;
		float:right; 
	}
	
	.nameDiv img{
		float:left;
		height:16px;
		width:auto;
		-moz-transition: all 0.25s ease;
		-webkit-transition: all 0.25s ease;
		-o-transition: all 0.25s ease;
		transition: all 0.25s ease;
		position:relative;	 
	}
	
	.nameDiv:hover img{
		-webkit-transform: translate(35px,35px)scale(6.25);
		-moz-transform: translate(35px,35px) scale(6.25);
		-o-transform: translate(35px,35px) scale(6.25);
		transform: translate(35px,35px) scale(6.25);
		 
	
 		z-index:1000; 			
		
	}	
	
	#images img{
		height:150px;
		width:auto; 
		-moz-transition: all 0.25s ease;
		-webkit-transition: all 0.25s ease;
		-o-transition: all 0.25s ease;
		transition: all 0.25s ease;
		position:relative;
		cursor:pointer;			
		
	}
	#images img:hover{
		-webkit-transform: scale(2.25);
		-moz-transform: scale(2.25);
		-o-transform: scale(2.25);
		transform: scale(2.25);
 		z-index:1000; 			
		
	}
	
 
	
		
	::-webkit-scrollbar {
		width: 12px;
	}
	
	::-webkit-scrollbar-track {
		-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
		border-radius: 10px;
	}
	
	::-webkit-scrollbar-thumb {
		border-radius: 10px;
		-webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.5);
	}	
	
	
	.imgHolder{
		width:45%;
		float:left;
		padding:5px;
		position:relative;			
		
	}
	.imgHolder a{
		float:right;
		font-size:10px;
		 
	}
	.imgHolder span{
		float:right;
		font-size:14px;
		 
	}	
	#croper{display:none;}
	
	.textDescBox{
		background-color:#F7F7F7;
		color:#39C;
		padding:10px;
		font-size:16px;

		
		
		
	}
	

</style>


<script type="text/javascript">

	var nodes = [], links = [], activeId = '', activeFullId = '', useCords = {}, descObject = null, videoMetaList;


	jQuery(document).ready(function($) {
		
		
		//this is all taken from the network proper JS
		
		$.get('abstracts.txt', function(data) {
			var descStore = $.rdf.databank([],
			  { base: 'http://www.dbpedia.org/',
				namespaces: { 
				  dc: 'http://purl.org/dc/elements/1.1/', 
				  wc: 'http://www.w3.org/2000/01/rdf-schema', 
				  lj: 'http://www.linkedjazz.org/lj/' } });	  
			
			
			/***********
			* 	The file we are loading is expected to be a triple dump in the format '<object> <predicate> <object> .\n'
			*   Note the space after the final object and the '.' and the \n only
			************/	  
			var triples = data.split("\n");
			for (x in triples){			
				if (triples[x].length > 0){		
					try{		
						descStore.add(triples[x]);
					}
					catch(err){
						//if it cannot load one of the triples it is not a total failure, keep going
						console.log('There was an error processing the data file:');
						console.log(err);										
					}
				}
			}
	
	 
			descObject = descStore.dump()	
		});
			
		
		
			
		$.get('triples.txt', function(data) {
		   
				  
			tripleStore = $.rdf.databank([],
			  { base: 'http://www.dbpedia.org/',
				namespaces: { 
				  dc: 'http://purl.org/dc/elements/1.1/', 
				  foaf: 'http://xmlns.com/foaf/0.1/', 
				  lj: 'http://www.linkedjazz.org/lj/' } });	  
					
		
		var triples = data.split("\n");
		for (x in triples){			
			if (triples[x].length > 0){		
				try{		
					tripleStore.add(triples[x]);
				}
				catch(err){
 					//if it cannot load one of the triples it is not a total failure, keep going
					console.log('There was an error processing the data file:');
					console.log(err);										
				}
			}
		}

 
		tripleObject = tripleStore.dump()

	 	var allObjects = [];
	 	
		//we need to establish the nodes and links
		//we do it by making a string array and adding their ids to it, if it is unique in the string array then we can add the object to the node array
		for (x in tripleObject){	//each x here is a person
		
			
			if (allObjects.indexOf(String(x))==-1){
				allObjects.push(String(x));
				nodes.push({id: String(x)});
			}
			
			for (y in tripleObject[x]){		//this level is the types of relations, mentions, knows, etc. each y here is a realtion bundle
				for (z in tripleObject[x][y]){	//here each z is a relation					
					if (allObjects.indexOf(tripleObject[x][y][z].value)==-1){

						nodes.push({id: tripleObject[x][y][z].value});
						allObjects.push(tripleObject[x][y][z].value);
					}
					createLink(String(x),tripleObject[x][y][z].value);
				}
			} 
		} 
		
					
		//asign the number of connections each node has
		for (aNode in nodes){
			var connections = 0;
			for (aLink in links){
				if (links[aLink].source.id == nodes[aNode].id || links[aLink].target.id == nodes[aNode].id){
					connections++;
				}
				
				
				
			}
			nodes[aNode].connections = connections;
		}				
				
		nodes.sort(function(a,b) {
			return b.connections - a.connections;
		});					
				 
		$("#left").css("height",$(document).height()-25+"px");
		
		for (aNode in nodes){
			
			
			var useId = $.trim(decodeURI(nodes[aNode].id).split("\/")[decodeURI(nodes[aNode].id).split("\/").length-1]);
 			
			$("#left").append(
				$("<div>")
					.data("id",useId)
					.addClass('nameDiv')
					.data("fullId",useId)
					.attr("id",useId.replace(/%|\(|\)|\,|\./g,'')+"_holder")
					.append
					(
						$("<div>")
							.html(useId.replace(/\_/g,' ') + " <span style=\"font-size:8px\">(" + nodes[aNode].connections + ")</span>")
							/*.addClass(function(){return (fileNames.indexOf(useId+'.meta')==-1) ? "noImageYet" : "hasImage";})*/
							.append
							(
								$("<a>")
									.text('YouYube Search')
									.attr("href","#"+useId)
									.data("id",useId)
									.data("fullId",nodes[aNode].id)
									.click(function(){activeFullId = $(this).data("fullId"); loadYouTube($(this).data("id"));})				
							)				
							.append
							(
								function(){
										
										/*
										if (fileNames.indexOf(useId+'.png')!=-1){
										
											return $("<img>").attr("src","img/" + useId+'.png');
											
											
										}
										*/
							
								}
							)								
						)
					)
			
			 
			
			
		}
		
		updateList();

				
				
				
				
		
		
		
		
		
				
				
				
		//----------------------------------------		
		
		
		
		function loadYouTube(useId){
 			var utCode = '<object style="height=150px; width=200px"> <param name="movie" value="https://www.youtube.com/v/<id>?version=3&feature=player_embedded&controls=1&enablejsapi=1&modestbranding=1&rel=0&showinfo=0&autoplay=0"><param name="allowFullScreen" value="true"><param name="allowScriptAccess" value="always"><embed src="https://www.youtube.com/v/<id>?version=3&feature=player_embedded&controls=1&enablejsapi=1&modestbranding=1&rel=0&showinfo=0&autoplay=0" type="application/x-shockwave-flash" allowfullscreen="true" allowScriptAccess="always" width="200" height="150"></object>';			
			
			if (videoMetaList.indexOf(useId + ".meta")!=-1){	
				var hasVideo = useId + ".meta";			
			}else{
				var hasVideo = false;				
			}
			
 
			$("#results").fadeIn();		
		
			$("#results").empty();
			$("#results").text("Loading Videos");
			var start=0;
			
			activeId = useId;
			orgId = useId;
			console.log(useId);
			
			if (useId.search(/\(/) != -1 ){
				
				useId = useId.substr(0,useId.search(/\(/));
					
				
			}
			
			
			
				
			$.getJSON("https://gdata.youtube.com/feeds/api/videos?dataType=jsonp",
				{ 
				q: 	useId.replace(/\_/g,' ') + " jazz",
				orderby: "relevance",
				alt: "json",
				"max-results": 20,
				v: 2 
				},
			function(data) {
				$("#results").empty();
				
				
				
 				
				$("#results").append(
					$("<div>")
						.addClass('textDescBox')
						.text(descObject[activeFullId]['http://www.w3.org/2000/01/rdf-schema#comment'][0].value)
						.append(
							$("<a>")
								.text("Click to show the current Video")
								.css("display",function(){return (hasVideo) ? "inline" : "none";})
								.attr("href","#")
								.css("color","#ccc")
								.click(function(){
																	
									 
									$.get('img/' + hasVideo + "?random=" + Math.floor((Math.random()*99999)+1), function(data) {
								
										var objectCode = utCode.replace(/\<id\>/ig,data);  
									 
										$("#results .textDescBox").append(objectCode);
										
								
									});										
									
									
								})
						)
				);	
				
				
				$.each(data.feed.entry, function(i,results){
					
					var youTubeId = results.id.$t.split(':')[results.id.$t.split(':').length-1];
					
					var objHtml = utCode.replace(/\<id\>/g,youTubeId);


					$("#results").append(
						$("<div>")
							.append(
								objHtml
							)
							.append(
								$("<span>").text(results.title.$t)
							)		
							.append(
							
								$("<button>")
									.text("Use this video")
									.data("youTubeId",youTubeId)
									.data("orgId",orgId)
									.data("useId",useId)									
									.click(function(){
										
											var thisId = $(this).data("orgId").replace(/%|\(|\)|\,|\./g,'');									
											 
											$.getJSON("?",
												{
												dataType: 'json',
												useId: $(this).data("orgId"),
												append: false,
												link: $(this).data("youTubeId") 
												},
												function(data){
													
													$("#" + thisId + "_holder div").removeClass("noImageYet")
													$("#" + thisId + "_holder div").addClass("hasImage");
													
												}
											); 
									
											updateList()
											$("#results").empty();
									})
							
							)							
										
					);
					
					
				});

				/*				 
				$.each(data.responseData.results, function(i,results){
		
					$("#images").append(
						$("<div>")
							.addClass("imgHolder")
							.append(
								$("<img>")
									.attr("src", results.unescapedUrl)
									.attr("id", "img"+i) 
									.click(function(){ showCrop($(this)); })									
							)
							.append(
								$("<a>")
									.attr("href", results.originalContextUrl)
									.text(results.contentNoFormatting + ' from ' + results.visibleUrl)
							
							)								
							.append(
								$("<span>")
									.addClass("useThisOne")									
									.text(function(){ return (results.visibleUrl.search("wiki") != -1) ? "Wikipedia Image" : ""; })
							)
					)
						
						
						
					
					  //$("<img/>").attr("src", results.unescapedUrl).attr("id", "img"+i).appendTo("#images");
					  //$("#img"+i).wrap($("<a/>").attr("href", results.unescapedUrl));

				 
				});
				*/ 
						 		
				
 			});			
			
			
		
			
			
			
			
		}

		 
				
				
		function createLink(id1, id2){	
			var obj1 = null, obj2 = null;		
			for (q in nodes){
				if (nodes[q].id == id1){obj1 = q;}
				if (nodes[q].id == id2){obj2 = q;}	
			}		
			
			
			var customClass = "link_" + id1.split("/")[id1.split("/").length-1].replace(/%|\(|\)|\./g,''); 
			customClass = customClass + " link_" + id2.split("/")[id2.split("/").length-1].replace(/%|\(|\)|\./g,''); 
			
			links.push({source: nodes[obj1], target: nodes[obj2], distance: 5, customClass:customClass});		
		}		
		
		
		function updateList(){
			
			$(".nameDiv").removeClass("hasImage").removeClass("noImageYet");
			
			$.get('?getVideos=True', function(data) {
	
	
				data  = data.split(','); 
				videoMetaList = data;
				
				$(".nameDiv").each(function(){
					 
					if (data.indexOf($(this).data("fullId") + ".meta")!=-1){					
						$(this).addClass("hasImage");						
					}else{
						$(this).addClass("noImageYet");	
					}
					
					
				});
				
				
				
			});			
			
		}
		
				
			
		
	});
		
		
		
		
		
		
		
		
		
		
	});
 


</script>


</head>
<body>

    
    <div id="left">
    
    	
    
        
    
    
    </div>
    
	<div id="right">
		<div id="results"></div>


    </div>
	




</body>
</html>
