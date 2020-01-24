<?php


if (isset($_REQUEST['useId'])){


	$id = urldecode($_REQUEST['useId']);
	$dbId = urldecode($_REQUEST['dbId']);	
	$url = urldecode($_REQUEST['useUrl']);
	$cords = explode(",",$_REQUEST['useCord']);
	 
	$id = str_replace('"','',$id);
	
	// if (strtolower(substr($url,strlen($url)-3,3)) != 'jpg'){
	// 	die('can not download that file');	
	// }
	
	
	$output = shell_exec('wget --user-agent="Chrome/19.0.1084.52" --output-document=tmp/' . $_SERVER['REMOTE_ADDR' ].'.jpg "' . $url . '" 2>&1');
	
	if (strpos($output,'saved')===false){
		die('could not download source image');	
	}
	
	$output = shell_exec('convert -crop ' . $cords[2] . 'x' . $cords[3] . '+' . $cords[0] . '+' . $cords[1] . ' tmp/' . $_SERVER['REMOTE_ADDR' ] . '.jpg -resize 100x100 tmp/' . $_SERVER['REMOTE_ADDR' ] . '.png 2>&1');
	echo $output;
	
	if (strlen($output)!=0){die("error croping the image");}
	
	$output = shell_exec('convert -size 100x100 xc:none -fill tmp/' . $_SERVER['REMOTE_ADDR' ]  . '.png -draw "circle 50,50 50,1" "round/' . $id . '.png"');

	$output = shell_exec('convert -colorspace gray "round/' . $id . '.png" "round/' . $id . '.png" ');
	
	$output = shell_exec('convert -colorspace gray -crop ' . $cords[2] . 'x' . $cords[3] . '+' . $cords[0] . '+' . $cords[1] . ' tmp/' . $_SERVER['REMOTE_ADDR' ] . '.jpg -resize 200x200 "square/' . $id . '.png" 2>&1');	
	

	//update the db
	$dbh = new PDO("mysql:host=localhost;dbname=transcripts", "linkedjazz", "linkedjazz");
	
	$instrument = "";
	if (isset($_REQUEST['instrument'])){
		$instrument = $_REQUEST['instrument'];
	}

	$sth = $dbh->prepare('UPDATE `authority` SET `image` = ?, `instrument` = ? WHERE `id` = ?');
	$sth->execute(array($id . '.png', $instrument, $dbId));


}






	$dbh = new PDO("mysql:host=localhost;dbname=transcripts", "linkedjazz", "linkedjazz");
	if ($dbh){
		
			//load the text			
			$sth = $dbh->prepare('SELECT * FROM `authority`');
			$sth->execute();				
			$people = $sth->fetchAll();	
			
			$jsString = '';
			
			foreach ($people as &$aPerson) {				
				$jsString = $jsString . "names['" . str_replace("'","%27",$aPerson['uri']). "'] = { 'dbId' : " . $aPerson['id']  . ", 'name' : '" .htmlspecialchars($aPerson['name'], ENT_QUOTES)  . "', image : \"" . $aPerson['image'] . "\" };\n";				
			}

			$jsString = $jsString . "names['" . str_replace("'","%27","<http://dbpedia.org/resource/Woody_Shaw>"). "'] = { 'dbId' : " . 99999  . ", 'name' : '" .htmlspecialchars("Woody Shaw", ENT_QUOTES)  . "', image : \"" . "" . "\" };\n";				

			
			$sth = $dbh->prepare('SELECT * FROM `transcripts`');
			$sth->execute();				
			$people = $sth->fetchAll();	
			
			$jsString2 = '';
			
			foreach ($people as &$aPerson) {				
				$jsString2 = $jsString2 . "transcripts['" . $aPerson['intervieweeURI']. "'] = '" . $aPerson['intervieweeURI'] . "';";				
			}			
		
	}








 
?>


<!DOCTYPE HTML>
<html>
<head>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
 <script src="jquery.Jcrop.min.js"></script>

<link href="jquery.Jcrop.min.css" rel="stylesheet" type="text/css" />


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
		margin-left:30%;
		width:69%;
		background-color:#CCC;
		position:fixed;
		height:600px;
		overflow:auto;
	}
	#left span{
		padding:10px;
		
	}

	.noImageYet, .hasImage{margin:5px; height:30px;}
	
	.noImageYet{background-color:#FFD2D2;}
	
	.noImageYet:hover{
		background-color:#FAFFB4;
		
	}
	.hasImage{background-color:rgb(193, 255, 203);}	

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
	
	#images{
		overflow:auto;
		
	}
	#images img{
		height:150px;
		width:auto; 
		/*
		-moz-transition: all 0.25s ease;
		-webkit-transition: all 0.25s ease;
		-o-transition: all 0.25s ease;
		transition: all 0.25s ease;
		*/
		position:relative;
		cursor:pointer;			
		
	}
	/*
	#images img:hover{
		-webkit-transform: scale(2.25);
		-moz-transform: scale(2.25);
		-o-transform: scale(2.25);
		transform: scale(2.25);
 		z-index:1000; 			
		
	}
	*/
 
	
		
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
	
	
	#leftDoneLabel {
		cursor:pointer; 
		
		
	}
	
	h5{
		margin:2px;
		padding:2px;
	}
	#images .imgHolder{
		border-bottom:solid 2px #E8E8E8;
	}
	#images .imgHolder:nth-child(2n){
		border-left:solid 2px #E8E8E8;
 		
	}

</style>


<script type="text/javascript">

	var nodes = [], links = [], activeDbId ='', activeId = '', useCords = {}, names = {}, transcripts = {};
	var icons = ["Clarinet_icon_1843.jpg","Double_bass.jpg","Drum_icon_1534.jpg","Flute_icon_29082.jpg","Guitar_icon_1031.jpg","Musical_notes_icon_23486.jpg","Percussion_icon_11895.jpg","Piano_Keyboard_icon_28756.jpg","Saxophone_icon_1645.jpg","Trombone_icon_4925.jpg","Trumpet_icon_11079.jpg","Violin_icon_12328.jpg","Vocals_icon_55442.jpg","Woodwind_icon_8783.jpg","Xylophone_icon_11632.jpg"];
	<?=$jsString?>
	<?=$jsString2?>


	jQuery(document).ready(function($) {
		
		
		
		for (aName in names){
			
			
			var useId = $.trim(decodeURI(aName).split("resource/")[decodeURI(aName).split("resource/").length-1]).replace('>','');
 
 			//not a dbpedia, use their name
			if (aName.search("dbpedia") == -1){
				
				var useId = decodeURI(names[aName].name).replace(/\s/g,'_');
				//console.log(aName.search("dbpedia") ,aName,useId);
			}
 
 			if (names[aName].image == ''){
				var addToo = $("#leftUndone");
			}else{
				var addToo = $("#leftDone");
			}
 
			addToo.append(
				$("<div>")
					.data("id",useId)
					.addClass('nameDiv')
					.attr("id",useId.replace(/%|\(|\)|\,/g,'')+"_holder")
					.append
					(
						$("<div>")
							.html((transcripts.hasOwnProperty(aName)) ? useId.replace(/\_/g,' ') + " <strong>[TRANSCRIPT]</strong>" : useId.replace(/\_/g,' '))
							.addClass(function(){return (names[aName].image=="") ? "noImageYet" : "hasImage";})
							
							// .append
							// (
							// 	$("<a>")
							// 		.text('[Google Search]')
							// 		.attr("href","#"+useId)
							// 		.data("id",useId)
							// 		.data("dbId",names[aName].dbId)
							// 		.click(function(){
										
							// 			$(".noImageYet").css("background-color","#FFD2D2");
										
							// 			$(this).parent().css("background-color","#97C6FF");
										
							// 			loadImages($(this).data("id"),$(this).data("dbId"));
		
										
 						// 				event.preventDefault();
							// 			return false;
										
												
							// 			})				
							// )			
							.append
							(
								$("<a>")
									.text('[Use Icon]')
									.css("margin-right","10px")
									.attr("href","#"+useId)
									.data("id",useId)
									.data("dbId",names[aName].dbId)
									.click(function(event){


										var localId = $(this).data("id");
										var localDbId = $(this).data("dbId");



										console.log()
										$("#images").empty();	
										for (var x in icons){
											$("#images").append(
												$("<img>")
													.data("id", localId )
													.data("dbId",localDbId)
													.data("instrumentName",icons[x])
													.data("imageUrl","https://linkedjazz.org/image/instruments_small/"+icons[x])
													.attr("src","https://linkedjazz.org/image/instruments_small/"+icons[x])
													.click(function(e){



														window.location ="?useId=" + encodeURIComponent($(this).data("id")) + "&dbId=" + $(this).data("dbId") + "&useUrl=" + $(this).data("imageUrl") + "&instrument=" + $(this).data("instrumentName") + "&useCord=" + 0 + "," + 0 + "," + 255 + "," + 255;




													})


											)

										}


									
										// $(".noImadgeYet").css("background-color","#FFD2D2");
										
										// $(this).parent().css("background-color","#97C6FF");
									
										// var url = prompt("What is the complete URL to the image?");	
									 // 	activeId = $(this).data("id");
									 // 	activeDbId = $(this).data("dbId");
										// if (url != "" && url != null){showCrop(url);}
										
										
										
										
 										event.preventDefault();
										return false;
										
										
									;})				
							)

							.append
							(
								$("<a>")
									.text('[Manual Input]')
									.css("margin-right","10px")
									.attr("href","#"+useId)
									.data("id",useId)
									.data("dbId",names[aName].dbId)
									.click(function(event){
									
										$(".noImageYet").css("background-color","#FFD2D2");
										
										$(this).parent().css("background-color","#97C6FF");
									
										var url = prompt("What is the complete URL to the image?");	
									 	activeId = $(this).data("id");
									 	activeDbId = $(this).data("dbId");
										if (url != "" && url != null){showCrop(url);}
										
										
										
										
 										event.preventDefault();
										return false;
										
										
									;})				
							)												
							.append
							(
								$("<a>")
									.text('[Authority]')
									.css("margin-right","20px")
									.attr("href",aName.replace(/<|>/g,''))
									.attr("target","_blank")
									.data("uri", aName)
									.data("id",useId)
									.data("dbId",names[aName].dbId)
									.css("visibility", (aName.search('linkedjazz')!=-1) ? "hidden" : "visible")		
							)					
							.append
							(
								function(){
										
										if (names[aName].image != ''){
										
											return $("<img>").attr("src","/image/round/" + names[aName].image);
											
											
										}
										
							
								}
							)								
						)
					)
			
			
			
			
			
		}
		function loadImages(useId,dbId){

			$("#croperImages").empty();
			$("#croper").fadeOut();
			$("#images").fadeIn();		
		
			$("#images").empty();
			$("#images").text("Loading Images");
			var start=0;
			
			activeId = useId;
			activeDbId = dbId;
			
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
					addImages(data);  
	
					
					//try a different search pattern		   
					$.getJSON("https://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=" + useId + ' jazz' + "&callback=?",
						{
						unescapedUrl: "any",
						safe:"moderate",
						rsz: "8", 
						as_filetype: "jpg" 
						},
						function(data) {
							 
							addImages(data);  
						
					});							   
					   
					   
				  
			});
 
		
			
		}
		
		
		function addImages(data){
			
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
								.attr("target","_blank")
								.text(results.contentNoFormatting + ' from ' + results.visibleUrl)
						
						)								
						.append(
							$("<span>")
								.addClass("useThisOne")									
								.text(function(){ return (results.visibleUrl.search("wiki") != -1) ? "Wikipedia Image" : ""; })
						)
				)
				
			});				
			
		}
		
		
		
		function showCrop(dom){
		

			$("#images").fadeOut();
			
			
			$("#croperImages").empty();
			$("#croper").fadeIn();
			
			
 			if(typeof dom == "string"){
				
				$("#croperImages").append(
					$("<img>")
						.attr("src",dom)	 
					
				)		
			}else{
			
				
				$("#croperImages").append(
					$("<img>")
						.attr("src",$(dom).attr("src"))	 
					
				)
				
			}
			
			$('#croperImages img').Jcrop({
				onSelect: function(c){useCords = c;},
				aspectRatio: 1.0
			});			
			
	
			
		}		
		
			
		$("#buttonOkay").click(function(){
			
			
			var useUrl = encodeURIComponent($("#croperImages img").attr("src"));

			alert(activeId)
			
			window.location ="?useId=" + encodeURIComponent(activeId) + "&dbId=" + activeDbId + "&useUrl=" + useUrl + "&useCord=" + useCords.x + "," + useCords.y + "," + useCords.w + "," + useCords.h;

			
			
			
			
			
		});			
		
		
		$("#leftDoneLabel").click(function(){
			
			$("#leftDone").toggle();
			
			
		});
			
	});	
		 

</script>


</head>
<body>

    
    <div id="left">
    
    	<span id="leftDoneLabel"><h5>Completed (Has Image). <button class="btn-mini">Click to show</button></h5></span>
    	<div id="leftDone" style="display:none"></div>
        <hr>
    	<span id="leftUndoneLabel"><h5>No Image Yet.</h5></span>
    	<div id="leftUndone" ></div>
        
    
    
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
