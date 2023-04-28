function quetzal_shs_ajax(page, scrollToBar)
{
	let el = document.querySelector("#quetzal_shs_search_bar");
	if(el == null)
		return;
	let form = new FormData(el.querySelector("form"));
	if(page != null)
		form.append("page", page);
	var xhr = new XMLHttpRequest();
	xhr.open('POST', options.rest_url + "v1/quetzal_shs_endpoint", true);
	xhr.onload = function ()
	{
		let json = JSON.parse(this.responseText);
		//console.log(json);
		let results = null;
		if(document.querySelector("#quetzal_shs_results") == null){
			results = el.querySelector("#results");
			if(results == null) {
				results = document.createElement("div");
				results.id = "results";
				el.appendChild(results);
			}

		}
		else {
			results = document.querySelector("#quetzal_shs_results");
		}
		results.innerHTML = "";

		/*
			quetzal_read_html_ids ritorna l'html creato dal back-office e gli id di tutti elementi
			al suo interno, che andranno matchati con quelli in arrivo dalla chiamata ajax.
			In questo modo riesco a ricreare la grafica creata nel back-office per ogni singolo elemento
			nel risultato della chiamata.
		*/
		var html_ids = quetzal_read_html_ids();
		var ids = html_ids["ids"];
		for(var i = 0; i < json.length; i++)
		{
			//results.innerHTML += "<p>" + json[i].title + "</p>";
			var div = document.createElement('div');
			var htmlString = html_ids["html"];
			div.innerHTML = htmlString.trim();

			for(var k = 0; k < ids.length; k++)
			{
				var obj = null;
				// se l'id contiene un doppio undercose (esempio meta__mg_autore) splitto l'id in due parti
				// per usare la prima parte come prima chiave in json[i][meta][mg_autore] e mg_autore come secondo oggetto dentro il json
				var ida = ids[k].split("__");
				let a = ida[0]; // key meta
				let b = ida[1]; // key mg_autore
				let j = json[i][a];
				if(Array.isArray(j)) // se sto passando category__name, il contentuo è un array di object
				{
					for(var j_index = 0; j_index < j.length; j_index++){
						if(obj == null)
							obj = "";
						if(j_index < j.length-1)
							obj += j[j_index][b] + " - ";
						else
							obj += j[j_index][b];
					}
				}
				else
				{
					if(ida.length > 1)
						obj = json[i][a][b];
					else
						obj = json[i][ids[k]];
				}

				var e = div.querySelector("#"+ids[k]);
				if(e != null)
				{
					if(e.firstChild != null)
						e.firstChild.data = obj != null ? obj : ""; // modifico solo il testo (firstChild) e non l'eventuale HTML ulteriore che potrebbe essere dentro all'elemento
					// se il tipo di elemento è un <a></a> setto l'href con il permalink (link all'articolo) che arriva dal json
					if(e.nodeName == "A" && json[i]["permalink"] != null)
						e.href = json[i]["permalink"];
					// se il tipo di elemento è un <img> setto src con il thumbnail che arriva dal json
					if(e.nodeName == "IMG" && json[i]["thumbnail"] != null){
						let url = json[i]["thumbnail"];
						if(typeof(url) == "string")
							e.src = url;
						else if(e.src == null)
							e.alt = "";
					}
						
				}
			}
			results.appendChild(div);
		}

		if(json.length > 0 && json[0]['max_num_pages'] > 1)
		{
			let mainDiv = document.querySelector("#quetzal_shs_results");

			let currentPage = mainDiv.querySelector("#quetzal_shs_currentPage");
			if(currentPage == null){
				currentPage = document.createElement("span");
				currentPage.id = "quetzal_shs_currentPage";
				currentPage.innerHTML = json[0]['paged'] + "/" + json[0]['max_num_pages'];
				results.appendChild(currentPage);
			}
			else{
				currentPage.innerHTML = json[0]['paged'] + "/" + json[0]['max_num_pages'];
			}

			if (mainDiv.querySelector("#nextButton") == null)
			{
				let back = document.createElement("button");
				back.id = "quetzal_shs_backButton";
				back.classList.add("btn");
				back.classList.add("quetzal-shs-btn-pagination");
				back.innerHTML = "Back";
				let backPage = json[0]['paged'] -1;
				if(backPage <= 1)
					backPage = 1;
				back.onclick = function(){ 
					quetzal_shs_ajax(backPage, true);
				};
				
				let next = document.createElement("button");
				next.id = "quetzal_shs_nextButton";
				next.classList.add("btn");
				next.classList.add("quetzal-shs-btn-pagination");
				next.innerHTML = "Next";
				let nextPage = json[0]['paged'] + 1;
				if(nextPage >= json[0]['max_num_pages'])
					nextPage = json[0]['max_num_pages'];
				next.onclick = function(){
					quetzal_shs_ajax(nextPage, true);
				};

				results.appendChild(next);
				results.appendChild(back);
			}
			else
			{
				let back = mainDiv.querySelector("#quetzal_shs_backButton");
				let backPage = json[0]['paged'] - 1;
				if(backPage <= 1)
					backPage = 1;
				back.onclick = function(){ quetzal_search_ajax(backPage); };

				let next = mainDiv.querySelector("#quetzal_shs_nextButton");
				let nextPage = json[0]['paged'] + 1;
				if(nextPage >= json[0]['max_num_pages'])
					nextPage = json[0]['max_num_pages'];
				next.onclick = function(){ quetzal_search_ajax(nextPage); };
			}
		}
		
		if(scrollToBar != null && scrollToBar == true){
			document.querySelector("#quetzal_shs_results").scrollIntoView(); 
		}
	};
	xhr.send(form);
}

function quetzal_read_html_ids(){
	var htmlString = document.querySelector("#quetzal_shs_results_template").innerHTML;
	var div = document.createElement('div');
	div.innerHTML = htmlString.trim();
	var children = [];
	quetzal_shs_recursiveFindChildren(children, div);
	
	var a = [];
	for(var child of children){
		if(child.id)
			a.push(child.id);
	}
	return {"html": htmlString, "ids": a };
}

function quetzal_shs_recursiveFindChildren(elements, el){
	for (let i = 0; i < el.children.length; i++)
	{
		elements.push(el.children[i]);
		if (el.children[i].children.length > 0) 
		quetzal_shs_recursiveFindChildren(elements, el.children[i]);
		else
			el.children[i].innerHTML = ""; // pulisco il testo presente utilizzato come sample preview dal back-office
	}
}

function quetzal_shs_save_html(name)
{
	let data = {"bar_name": name, "html_code": "", "nonce": options.nonce};
	//let html = document.getElementById(name).value;
	//let textarea = document.getElementById(name).firstChild;
	var editor = ace.edit(name);
	let html = editor.getValue();
	if(html == "")
		return;
	data['html_code'] = html;
	
	var xhr = new XMLHttpRequest();
	xhr.open('POST', options.rest_url + "v1/quetzal_shs_save_html", true);
	xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
	xhr.setRequestHeader('X-WP-Nonce', options.nonce);
	xhr.onload = function() {
		document.getElementById("preview_"+data.bar_name).innerHTML = html;
	};
	xhr.send(JSON.stringify(data));
}

function quetzal_shs_select_inflate()
{
	// check if shs_search_bar has a <select> tag, then popolate the select
	let el = document.querySelector("#quetzal_shs_search_bar");
	if(el != null)
	{
		let select = document.getElementsByTagName("select");
		if(select != null && select.length >= 1)
		{
			select = select[0];
			let form = new FormData();
			/*	- attribute inflateWith split example -
				inflateWith == post_type__cartoon_character
				inflate[0] -> post_type
				inflate[1] -> cartoon_character
			*/
			let inflate = select.getAttribute("inflateWith").split("__");
			form.append(inflate[0], inflate[1]);
			if(form)
			{
				var xhr = new XMLHttpRequest();
				xhr.open('POST', options.rest_url + "v1/quetzal_shs_endpoint", true);
				xhr.onload = function (){
					let json = JSON.parse(this.responseText);
					for(var i = 0; i < json.length; i++)
						select.innerHTML += '<option id="'+json[i].title+'">' + json[i].title + '</option>';
				};
				xhr.send(form);
			}
		}
	}
}

function simple_html_search_select_event(){
	window.addEventListener('load', function () {
		quetzal_shs_select_inflate();
	});
}