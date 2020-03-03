(function ($) {

// Variable to get the URL of the page 
var url = $(location).attr('href'); 

// Variable to store the query parameter in the URL 
var term = url.split('?topic=')[1]; 
// Variable to replace encoded spaces and replace with regular spaces 

if (term !== "" && term !== undefined) { 
var btnTerm = term.replace(/%20/g," ").replace(/ /g,"-").toLowerCase(); 
var filterTerm = term.replace(/%20/g," "); 
} 

//Function to filter table based on the query parameter 
$(document).on("wb-ready.wb-tables", function () { 

var tableAccents = $("#consultations").dataTable(); 
var table = $("#consultations").DataTable(); 

$('.dataTables_filter input').keyup(function () { 
tableAccents.fnFilter(AccentFilter(this.value), null, true); 
}); 

function AccentFilter(text) { 
var filter = ""; 
 for (var i = 0; i < text.length; i++) { 
var ch = text.charAt(i); 

switch (ch) { 
 //à a with grave 00E0 
 //À capital a with grave 00C0 
 //â a with circumflex 00E2 
 //Â capital A with circumflex 00C2 
 case 'â': 
ch = '[âa]'; 
break; 
case 'a': 
ch = '[âaà]'; 
 break; 
 case 'à': 
ch = '[àa]'; 
 break; 
 case 'Â': 
ch = '[ÂA]'; 
 break; 
case 'A': 
ch = '[ÂAÀ]'; 
 break; 
case 'À': 
ch = '[ÀA]'; 
 break; 

//ç c with cedilla 00E7 
 //Ç capital C with 00C7 
 case 'Ç': 
ch = '[ÇC]'; 
break; 
 case 'C': 
ch = '[ÇC]'; 
break; 
 case 'ç': 
ch = '[çc]'; 
break; 
 case 'c': 
ch = '[çc]'; 
 break; 

//è e with grave 00E8 
 //È capital e with grave 00C8 
 //é e with acute 00E9 
 //É capital e with acute 00C9 
 //ê e with circumflex 00EA 
 //Ê capital E with circumflex 00CA 
 //ë e with dieresis 00EB 
 //Ë capital E with dieresis 00CB 
 case 'e': 
ch = '[éeèë]'; 
 break; 
 case 'é': 
ch = '[ée]'; 
 break; 
 case 'ê': 
ch = '[êe]'; 
 break; 
 case 'è': 
ch = '[èe]'; 
 break; 
case 'ë': 
ch = '[ëe]'; 
 break; 
 case 'E': 
ch = '[ÉEÈë]'; 
 break; 
 case 'É': 
ch = '[ÉE]'; 
 break; 
 case 'Ê': 
ch = '[ÊE]'; 
break; 
 case 'È': 
ch = '[ÈE]'; 
 break; 
 case 'Ë': 
ch = '[ËE]'; 
 break; 

//î i with circumflex 00EE 
//Î capital I with circumflex 00CE 
 //ï i with dieresis 00EF 
 //Ï capital I with dieresis 00CF 
 case 'î': 
ch = '[îi]'; 
 break; 
case 'i': 
ch = '[îiï]'; 
 break; 
 case 'ï': 
ch = '[ïi]'; 
 break; 
 case 'Î': 
ch = '[ÎI]'; 
 break; 
 case 'I': 
ch = '[ÎIÏ]'; 
 break; 
 case 'Ï': 
ch = '[ÏI]'; 
 break; 

//ô o with circumflex 00F4 
 //Ô capital O with circumflex 00D4 
 case 'ô': 
ch = '[ôo]'; 
 break; 
 case 'o': 
ch = '[ôo]'; 
 break; 
 case 'Ô': 
ch = '[ÔO]'; 
 break; 
 case 'O': 
ch = '[ÔO]'; 
 break; 

//ù u with grave 00F9 
 //Ù capital U with grave 00D9 
 //û u with circumflex 00FB 
 //Û capital U with circumflex 00DB 
 //ü u with dieresis 00FC 
 //Ü capital U with dieresis 00DC 
 case 'û': 
ch = '[ûu]'; 
 break; 
case 'u': 
ch = '[ûuù]'; 
 break; 
 case 'ù': 
ch = '[ùu]'; 
 break; 
 case 'ü': 
ch = '[üu]'; 
 break; 
 case 'Û': 
ch = '[ÛU]'; 
 break; 
 case 'U': 
ch = '[ÛUÙ]'; 
 break; 
 case 'Ù': 
ch = '[ÙU]'; 
break; 
case 'Ü': 
ch = '[ÜU]'; 
break; 

//ÿ y with dieresis 00FF 
 //Ÿ capital Y with dieresis 0178 
 case 'ÿ': 
ch = '[ÿy]'; 
break; 
 case 'y': 
ch = '[ÿy]'; 
 break; 
 case 'Ÿ': 
ch = '[ŸY]'; 
 break; 
 case 'Y': 
ch = '[ŸY]'; 
 break; 
 default: 
 break; 
} 

filter += ch; 
 } 

return filter; 
} 


if (term !== "" && term !== undefined) { 

$("#" + btnTerm).addClass("btn-primary"); 

table 
	.columns(1) 
.search(filterTerm) 
	.draw(); 
	} 
});
}(jQuery));