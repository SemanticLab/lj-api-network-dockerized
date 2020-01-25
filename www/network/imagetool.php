<?php


if (isset($_REQUEST['useId'])){


	$id = urldecode($_REQUEST['useId']);
	$url = urldecode($_REQUEST['useUrl']);
	$cords = explode(",",$_REQUEST['useCord']);
	 
	
	if (strtolower(substr($url,strlen($url)-3,3)) != 'jpg'){
		die('can not download that file');	
	}
	
	
	$output = shell_exec('wget --user-agent="Chrome/19.0.1084.52" --output-document=tmp/' . $_SERVER['REMOTE_ADDR' ].'.jpg "' . $url . '" 2>&1');
	
	if (strpos($output,'saved')===false){
		die('could not download source image');	
	}
	
	$output = shell_exec('convert -colorspace gray -crop ' . $cords[2] . 'x' . $cords[3] . '+' . $cords[0] . '+' . $cords[1] . ' tmp/' . $_SERVER['REMOTE_ADDR' ] . '.jpg -resize 100x100 tmp/' . $_SERVER['REMOTE_ADDR' ] . '.png 2>&1');
	echo $output;
	
	if (strlen($output)!=0){die("error croping the image");}
	
	$output = shell_exec('convert -size 100x100 xc:none -fill tmp/' . $_SERVER['REMOTE_ADDR' ]  . '.png -draw "circle 50,50 50,1" "img/' . $id . '.png"');
	

}

















if ($handle = opendir('img/')) {
	$jsFileNames='';
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
			if (strpos($entry,'.png')!==false){
				$jsFileNames .= "'" . $entry . "',";
			}
        }
		
		
    }
	$jsFileNames = substr($jsFileNames,0,strlen($jsFileNames)-1);
	$jsFileNames = "var fileNames = [" . $jsFileNames . "];";
    closedir($handle);
}
 
?>


<!DOCTYPE HTML>
<html>
<head>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script src="js/jquery.rdfquery.core.min-1.0.js"></script>
<script src="js/jquery.Jcrop.min.js"></script>

<link href="css/jquery.Jcrop.min.css" rel="stylesheet" type="text/css" />


<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Linked Jazz Network Image Tool</title>

<style>

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
	

</style>


<script type="text/javascript">

	var nodes = [], links = [], activeId = '', useCords = {};

	<?=$jsFileNames?>



	jQuery(document).ready(function($) {
		
		
		//this is all taken from the network proper JS
		
			
		$.get('data/triples.txt', function(data) {
		   
				  
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
					.attr("id",useId.replace(/%|\(|\)|\,/g,'')+"_holder")
					.append
					(
						$("<div>")
							.html(useId.replace(/\_/g,' ') + " <span style=\"font-size:8px\">(" + nodes[aNode].connections + ")</span>")
							.addClass(function(){return (fileNames.indexOf(useId+'.png')==-1) ? "noImageYet" : "hasImage";})
							.append
							(
								$("<a>")
									.text('Google Search')
									.attr("href","#"+useId)
									.data("id",useId)
									.click(function(){loadImages($(this).data("id"));})				
							)				
							.append
							(
								function(){
										
										if (fileNames.indexOf(useId+'.png')!=-1){
										
											return $("<img>").attr("src","img/" + useId+'.png');
											
											
										}
										
							
								}
							)								
						)
					)
			
			 
			
			
		}
		
		
		
				
				
				
				
		
		
		
		
		
		$("#buttonOkay").click(function(){
			
			
			
			
			
			
			var useUrl = encodeURIComponent($("#croperImages img").attr("src"));
			
			window.location ="?useId=" + encodeURIComponent(activeId) + "&useUrl=" + useUrl + "&useCord=" + useCords.x + "," + useCords.y + "," + useCords.w + "," + useCords.h;

			
			
			
			
			
		});
		
		
				
				
				
		//----------------------------------------		
		
		
		
		function loadImages(useId){

			$("#croperImages").empty();
			$("#croper").fadeOut();
			$("#images").fadeIn();		
		
			$("#images").empty();
			$("#images").text("Loading Images");
			var start=0;
			
			activeId = useId;
			
			if (useId.search(/\(/) != -1 ){
				
				useId = useId.substr(0,useId.search(/\(/));
					
				
			}
			
			
			useId = useId.replace(/\_/g,' ');
			
				
			$.getJSON("https://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=" + useId  + " wikimedia" + "&callback=?",
				{
				unescapedUrl: "any",
				safe:"moderate",
				rsz: "8", 
				as_filetype: "jpg", 
				},
			function(data) {
				$("#images").empty();
				start = data.responseData.cursor.pages[7].start;
				 
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
				
				
			  
					//whaterver, load more... 
					$.getJSON("https://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=" + useId + ' jazz' + "&callback=?",
						{
						unescapedUrl: "any",
						safe:"moderate",
						rsz: "8", 
						as_filetype: "jpg" 
						},
					function(data) {
						 
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
						




						});
						
 					});			
									
				
				return false;
			});			
			
			
		
			
			
			
			
		}
		
		
		function showCrop(dom){
		

			$("#images").fadeOut();
			
			
			$("#croperImages").empty();
			$("#croper").fadeIn();
			
			
			$("#croperImages").append(
				$("<img>")
					.attr("src",$(dom).attr("src"))	 
				
			)
			$('#croperImages img').Jcrop({
				onSelect: function(c){useCords = c;},
				aspectRatio: 1.0
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
			
		
	});
		
		
		
		
		
		
		
		
		
		
	});
 


</script>


</head>
<body>

    
    <div id="left">
    
    	
    
        
    
    
    </div>
    
	<div id="right">
		<div id="images"></div>

    	<div id="croper">
        	<div id="croperImages"></div>
        	<button id="buttonOkay">Okay</button>
        </div>
    </div>
	




</body>
</html>
