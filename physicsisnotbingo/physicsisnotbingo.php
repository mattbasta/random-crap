<html>
<head>
<title>PHYSICS IS NOT BINGO</title>
<style type="text/css">
body {
	font-size:120px;
	text-transform:uppercase;
	font-family:sans-serif;
	font-weight:bold;
}
</style>
</head>
<body>
<a href="?<?=uniqid()?>">
Physics 
<?
$responses = array(
	"is phun",
	"is phantastic",
	"students play physo",
	"can be a hobby",
	"is fun on the weekends",
	"is better than Monopoly",
	"is safe for pregnant mothers",
	"does not cause cancer",
	"should be done in pencil",
	"is a matter of matter",
	"is heavy",
	"can be better than sex",
	"is physics",
	"is never complicated",
	"is right behind you!!!",
	"might be singular or plural",
	"is less toxic than chemistry",
	"can be considered a sport",
	"is not psychics",
	"is never on facebook",
	"deserves a round of applause",
	"made little sense at the time",
	"is shorter than ten words",
	"votes Democratic. Who knew?",
	"knows what you did last summer",
	"gives you wings",
	"offers free hugs on Tuesday",
	"is open 24/7",
	"is free. take some.",
	"can be demanding at times",
	"is not bingo"
);
echo $responses[array_rand($responses)];
?>
</a>
</body>
</html>