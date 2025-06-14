var nodes = document.getElementsByClassName('node');
for (var node of nodes) {
	var anodes = node.getElementsByTagNameNS('http://www.w3.org/2000/svg', 'g');
	const anode = anodes.length == 1 ? anodes[0]: null;
	if (!anode) {
		continue;
	}

	const polygons = anode.getElementsByTagNameNS('http://www.w3.org/2000/svg', "polygon");
	const polygon = polygons.length == 1 ? polygons[0] : null;
	anode.appendChild(polygon);

	var textEls = anode.getElementsByTagNameNS('http://www.w3.org/2000/svg', 'text');
	var textEl = textEls.length == 1 ?  textEls[0]: null;
	anode.appendChild(textEl);

	const points  = polygon.getAttribute('points');
	const coordinates = points.split(" ");
	const left_   = coordinates[0].split(",")[0];
	const top__   = coordinates[0].split(",")[1];
	const right_  = coordinates[1].split(",")[0];
	const top_    = coordinates[1].split(",")[1];
	const bottom_ = coordinates[2].split(",")[1];
	const width_  = left_ - right_;
	const height_ = bottom_ - top_;

	const newDropDown = document.createElementNS('http://www.w3.org/2000/svg', "foreignObject");
	newDropDown.setAttribute('transform', "translate(" + coordinates[1] + ")");
	newDropDown.setAttribute('width', '1em');
	newDropDown.setAttribute('height', height_);
	textEl.after(newDropDown);

	const div = document.createElement("div");
	div.setAttribute('style', "display:inline-block; width:fit-content;float:right"); 
	newDropDown.appendChild(div);

	const select = document.createElement("select");
	select.setAttribute('onChange', 'window.location.href=this.value');
	select.setAttribute('style', "width:1em");
	div.appendChild(select);

	const option0 = document.createElement("option");
	option0.setAttribute('value', "");
	option0.textContent='Pick an action';
	select.appendChild(option0);

	var urlEls = node.getElementsByTagName('a');
    if (urlEls.length == 1) {
		const urlEl = urlEls[0];
		const url = urlEl.getAttribute('xlink:href')
		const option1 = document.createElement("option");
		option1.setAttribute('value', url);
		option1.textContent='Subgraph';
		select.appendChild(option1);
		urlEl.remove();
	}
}
