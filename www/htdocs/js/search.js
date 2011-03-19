function initsearch()
{
	var geobutton = document.getElementById('search');
	geobutton.style.display = 'block';
	geobutton.index = 2;
	map.controls[google.maps.ControlPosition.TOP_RIGHT].push(geobutton);
}

var setInputBox = function(str)
{
	document.getElementById('inputbox').value = str;
}

var t;
var selectIndex = -1;
var limit = 0;
function show(id)
{
	selectIndex = -1;
	clearTimeout(t);
	document.getElementById(id).style.display = "block";
}

function hide(id)
{
	document.getElementById(id).style.display = "none";
}

function moveUp()
{
	removeHighlight();
	if(selectIndex >= 0)
		selectIndex--;
	updateHighlight();
	return false;
}

function moveDown()
{
	removeHighlight();
	if(selectIndex < limit - 1)
		selectIndex++;
	updateHighlight();
	return false;
}

function select()
{
	if(selectIndex >= 0)
		document.getElementById('li'+selectIndex).onclick();
	document.getElementById('inputbox').blur();
}

function removeHighlight()
{
	if(selectIndex >= 0)
		document.getElementById('li'+selectIndex).style.backgroundColor = 'inherit';
}

function updateHighlight()
{
	if(selectIndex >= 0)
		document.getElementById('li'+selectIndex).style.backgroundColor = '#CCCCFF';
}

function delayHide(id, delay)
{
	t = setTimeout("hide('"+id+"');", delay);
}
