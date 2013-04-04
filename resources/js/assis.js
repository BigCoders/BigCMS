var API_CRT = VAR_clientURL+'g/PHP/API_cart.php';
var API_PRD = VAR_clientURL+'g/PHP/API_product.php';
var API_HML = VAR_clientURL+'g/PHP/API_html.php';
var assis = {
	changeLang: function(el){
		var i = info_create('assis',{'.width':'300px'},el).infoContainer.empty();
		$C('H4',{innerHTML:'Cambiar idioma de los productos'},i);
		$A(VAR_allowedLangs).each(function(el,k){
			var d = $C('DIV',{},i);
			$C('INPUT',{className:'radio',type:'radio',name:'lang',value:k},d);
			$C('SPAN',{innerHTML:' ['+k+'] '+el},d);
		});
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancelar',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){z(i)},d,'assisButton');
	},
	shipping_changeStatus: function(el,pool){
		var ids = this.helper_getTableIDs(el.parentNode);
		var i = info_create('assis',{'.width':'300px'},el).infoContainer.empty();
		$C('H4',{innerHTML:'Cambiar status de los pedidos seleccionados'},i);
		$C('INPUT',{type:'hidden',name:'command',value:'shipping_update'},i);
		$C('INPUT',{type:'hidden',name:'cartHash',value:ids},i);
		$A(['Pendiente de pago','Pago realizado','Enviado','Cancelado','Devuelto']).each(function(el,k){
			var d = $C('DIV',{},i);
			$C('INPUT',{className:'radio',type:'radio',name:'shippingStatus',value:k},d);
			$C('SPAN',{innerHTML:' ['+k+'] '+el},d);
		});
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancelar',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){z(i)},d,'assisButton');

		function z(i){
//FIXME: necesitamos poner aquí un loader
			var p = $parseForm(i);
			ajaxPetition(API_CRT,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				//var newData = r.data[p.fieldName];
				//callbackElem.innerHTML = newData;
				//alert(print_r(r));
				info_destroy(i);
			});
		}
	},
	shipping_addTrack: function(el,pool){
		var ids = this.helper_getTableIDs(el.parentNode);
		var i = info_create('assis',{'.width':'300px'},el).infoContainer.empty();
		$C('H4',{innerHTML:'Añadir código de track a un pedido'},i);
$C('P',{innerHTML:'Momentáneamente no se olvide de seleccionar un único producto'},i);
		$C('INPUT',{type:'hidden',name:'command',value:'shipping_update'},i);
		$C('INPUT',{type:'hidden',name:'cartHash',value:ids},i);
		$C('INPUT',{type:'hidden',name:'shippingStatus',value:1},i);
		$C('INPUT',{name:'shippingTrack'},$C('DIV',{className:'inputTextSimple'},i));
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancel',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){z(i)},d,'assisButton');

		function z(i){
//FIXME: necesitamos poner aquí un loader
			var p = $parseForm(i);
			ajaxPetition(API_CRT,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				//var newData = r.data[p.fieldName];
				//callbackElem.innerHTML = newData;
				//alert(print_r(r));
				info_destroy(i);
			});
		}
	},
	shipping_viewShippingAddress: function(el,pool){
		var ids = this.helper_getTableIDs(el.parentNode);
		var i = info_create('assis',{'.width':'300px'},el).infoContainer.empty();
		if(ids.length < 1){
			$C('H4',{innerHTML:'No se ha seleccionado ningún elemento de la lista'},i);
//FIXME: poner ayuda
			var d = $C('UL',{className:'buttonHolder'},i);
			gnomeButton_create('Cerrar',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
			return;
		}
		$C('H4',{innerHTML:'Consultar dirección de envío de los pedidos seleccionados'},i);

		var ul = $C('UL',{},i);
//FIXME: TODO
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cerrar',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		z(i,ids,ul);

		function z(i,ids,ul){
//FIXME: necesitamos poner aquí un loader
			var p = {'command':'shipping_getInfo','cartHash':ids};
			ajaxPetition(API_CRT,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				for(var a in r.data){
					var elem = r.data[a];
					var li = $C('LI',{},ul);
					$C('DIV',{innerHTML:a},li);
					$C('DIV',{innerHTML:elem.shippingUserPhone},li);
					$C('DIV',{innerHTML:elem.shippingAddress},li);
					$C('DIV',{innerHTML:elem.shippingPostalCode},li);
					$C('DIV',{innerHTML:elem.shippingLocation},li);
					$C('DIV',{innerHTML:elem.shippingCountry},li);
				}
			});
		}
	},
	products_listInfo: function(el){
		var i = info_create('assis',{'.width':'300px'},el).infoContainer.empty();
		$C('H4',{innerHTML:'Listar productos de la base de datos ...'},i);

		var ul = $C('UL',{},i);
		$C('A',{innerHTML:'Productos sin imágenes',href:VAR_baseURL+'listWithoutImages'},$C('LI',{},ul));

		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancel',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){z(i)},d,'assisButton');
	},
	products_changeStatus: function(el,pool){
		var ids = this.helper_getTableIDs(el.parentNode);
		var i = info_create('assis',{'.width':'300px'},el).infoContainer.empty();
		$C('H4',{innerHTML:'Cambiar status de los productos seleccionados'},i);
		$C('INPUT',{type:'hidden',name:'command',value:'changeField'},i);
		$C('INPUT',{type:'hidden',name:'ids',value:ids},i);
		$C('INPUT',{type:'hidden',name:'fieldName',value:'productStatus'},i);
		$A(['Desactivado','Activado']).each(function(el,k){
			var d = $C('DIV',{},i);
			$C('INPUT',{className:'radio',type:'radio',name:'fieldText',value:k},d);
			$C('SPAN',{innerHTML:' ['+k+'] '+el},d);
		});
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancel',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){z(i)},d,'assisButton');

		function z(i){
//FIXME: necesitamos poner aquí un loader
			var p = $parseForm(i);
			ajaxPetition(API_PRD,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				//var newData = r.data[p.fieldName];
				//callbackElem.innerHTML = newData;
				for(var a in r.data){
					var tablerow = $_('row_'+a);if(!tablerow){continue;}
					var patt1 = new RegExp(a+':'+p.fieldName+'$');
					$A(tablerow.$T('DIV')).each(function(el){if(el.className.match(patt1)){el.innerHTML = r.data[a][p.fieldName];}});
				}
				info_destroy(i);
			});
		}
	},
	products_changePriceManual: function(el,pool){
//FIXME: comprobar que el lang esté dentro de allowed
		var ids = this.helper_getTableIDs(el.parentNode);
		var i = info_create('assis',{'.width':'300px'},el).infoContainer.empty();
		$C('H4',{innerHTML:'Cambiar el precio de los productos seleccionados'},i);
		$C('INPUT',{type:'hidden',name:'command',value:'update'},i);
		$C('INPUT',{type:'hidden',name:'ids',value:ids},i);
		var tbody = $C('TBODY',{},$C('TABLE',{},i));
		$A(ids).each(function(el,k){
			var product = jsonDecode(products[el]);
			var tr = $C('TR',{},tbody);
			$C('TD',{innerHTML:el,'.width':'20px'},tr);
			$C('INPUT',{name:'productPrice_'+VAR_langCode+'_'+el,value:product.productPrice},$C('DIV',{className:'inputTextSimple'},$C('TD',{},tr)));
		});
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancel',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){z(i)},d,'assisButton');

		function z(i){
//FIXME: necesitamos poner aquí un loader
			var p = $parseForm(i);
			ajaxPetition(API_PRD,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				//var newData = r.data[p.fieldName];
				//callbackElem.innerHTML = newData;
				//alert(print_r(r));
				info_destroy(i);
			});
		}
	},
	products_changePhoto: function(el,pool){
		var ths = this;
		var ids = this.helper_getTableIDs(el.parentNode);
		var i = info_create('assis',{'.width':'300px'},el).infoContainer.empty();
		$C('H4',{innerHTML:'Cambiar la imagen de portada de los productos seleccionados'},i);
		$C('INPUT',{type:'hidden',name:'command',value:'image_savePortraits'},i);
		var tbody = $C('TBODY',{},$C('TABLE',{},i));
		$A(ids).each(function(el,k){
			var product = jsonDecode(products[el]);
			//alert(print_r(product));
			var tr = $C('TR',{},tbody);
			var td = $C('TD',{innerHTML:el,'.width':'20px'},tr);
			var inputName = 'productPortrait_'+el;
			$C('INPUT',{type:'hidden',id:inputName,name:inputName,value:''},td);
			$C('DIV',{className:'dropable',ondragover:function(e){ths.dropable_over(e);},ondrop:function(e){ths.dropable_drop(e,inputName);}},$C('TD',{},tr));
		});
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancel',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){z(i)},d,'assisButton');

		function z(i){
//FIXME: necesitamos poner aquí un loader
			var p = $parseForm(i);
			ajaxPetition(API_PRD,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				//var newData = r.data[p.fieldName];
				//callbackElem.innerHTML = newData;
				//alert(print_r(r));
				info_destroy(i);
			});
		}
	},
	products_removeProductFromCategory: function(el,cat){
		var ids = this.helper_getTableIDs(el.parentNode);
		var p = new Array();
		p['command'] = 'update';
		p['ids'] = '';
		
		$A(ids).each(function(el,k){
			var product = jsonDecode(products[el]);
			var c = product.productCategories;
			var nc = c.replace(cat+',', '');
			p['ids'] += product.id+',';
			p['productCategories_'+product.id] = nc;
			
		});
		p['ids'] = p['ids'].slice(0,p['ids'].length - 1)
		
		ajaxPetition(API_PRD,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}window.location.reload();});
		
	},
	html_changeMetaInformation: function(el,pool){
//FIXME: comprobar que el lang esté dentro de allowed
		var ids = this.helper_getTableIDs(el.parentNode);
		var i = info_create('assis',{'.width':'400px'},el).infoContainer.empty();
		$C('H4',{innerHTML:'Cambiar el título y descripción de la plantilla seleccionada'},i);
		$C('INPUT',{type:'hidden',name:'command',value:'changeMetaInformation'},i);

		$A(ids).each(function(el,k){
			$C('H5',{innerHTML:el},i);
			var obj = jsonDecode(window[pool][el]);
			var tbody = $C('TBODY',{},$C('TABLE',{},i));
			var tr = $C('TR',{},tbody);
			$C('TD',{innerHTML:'Título de la página'},tr);
			$C('INPUT',{name:'HTMLtitle_'+VAR_langCode+'_'+el,value:obj.HTML_TITLE},$C('DIV',{className:'inputTextSimple'},$C('TD',{},tr)));

			var tr = $C('TR',{},tbody);
			$C('TD',{innerHTML:'Título de la página'},tr);
			$C('TEXTAREA',{name:'HTMLdescription_'+VAR_langCode+'_'+el,value:obj.HTML_DESCRIPTION},$C('DIV',{className:'inputTextSimple'},$C('TD',{},tr)));
		});

		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancel',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){z(i)},d,'assisButton');

		function z(i){
//FIXME: necesitamos poner aquí un loader
			var p = $parseForm(i);
			ajaxPetition(API_HML,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				//alert(print_r(r));
				info_destroy(i);
			});
		}
	},
	helper_checkThis: function(el){
		var status = el.checked;
		while(el.parentNode && el.tagName != 'TR'){el = el.parentNode;}
		if(status){el.className = el.className.replace(/ ?selected/,'')+' selected';}
		else{el.className = el.className.replace(/ ?selected/,'');}
	},
	helper_getTableIDs: function(el){
		if(el.className.match(/tableOptions/)){var prev = el.previousSibling;while(prev.previousSibling && prev.tagName != 'TABLE'){prev = prev.previousSibling};el = prev;}
		var inputs = $fix(el).$T('INPUT');
		var ids = [];$A(inputs).each(function(el){if((el.className !== 'checkbox' && el.className !== 'radio') || el.parentNode.tagName != 'TD'){return;}if(el.checked){ids.push(el.value);}});
		return ids;
	},
	addCategory: function(el){
		var h = $_('categoryHolder');
		var categoryID = el.className.match(/category_([0-9]+)/);
		if(!categoryID){return;}categoryID = categoryID[1];

		var input = h.nextSibling;
		var cID = ','+categoryID+',';
		if(input.value.match(cID)){return;}
		input.value += cID;
		input.value = input.value.replace(/,,/g,',');

		var nEl = el.cloneNode(1);
		nEl.onclick = function(){assis.removeCategory(nEl);}
		h.appendChild(nEl);
	},
	removeCategory: function(el){
		var h = $_('categoryHolder');
		var categoryID = el.className.match(/category_([0-9]+)/);
		if(!categoryID){return;}categoryID = categoryID[1];

		var input = h.nextSibling;
		var cID = ','+categoryID+',';
		input.value = input.value.replace(cID,'');

		el.parentNode.removeChild(el);
	},
	descriptionAdd: function(a){
		
	},
	dropable_drop: function(e,id){
		e.preventDefault();var dt = e.dataTransfer;var files = dt.files;
		if(files.length < 1){return;}
		/* Solo puede ser una única imágen por vez */
		var file = files[0];
		if(!file.type.match(/image.*/)){return;}
		var dest = $_(id);if(!dest){return;}
		var reader = new FileReader();
		reader.onloadend = function(){
			if(dest){dest.value = reader.result;}
			e.target.className += ' icon16 package-upgrade';
		};reader.readAsDataURL(file);
	},
	dropable_over: function(e){e.preventDefault();},
	imgDrop: function(e,h){
		e.preventDefault();var dt = e.dataTransfer;var files = dt.files;

		if(files.length < 1){return;}
		var tbody = $_('uploadList');
		$A(files).each(function(file){
			
			var reader = new FileReader();
			reader.onloadend = function(){
				var tr = $C('TR',{},tbody);
				$C('TD',{innerHTML:file.name},tr);
				$C('TD',{innerHTML:file.type},tr);
				$C('TD',{innerHTML:file.size},tr);
				var td = $C('TD',{},tr);
				$C('INPUT',{type:'hidden',name:'file'+Math.random()*1000,value:reader.result},td);
			};reader.readAsDataURL(file);
		});
	},
	imgDragOver: function(e){e.preventDefault();}
}
var form = {
	submit: function(el){while(el.parentNode && el.tagName!='FORM'){el = el.parentNode;}if(!el.parentNode){return;}el.submit();},
	changeField: function(e,a,api){
		e.preventDefault();
		e.stopPropagation();
		var elem = e.target;
		var m = elem.className.match(/([a-zA-Z0-9]+):([a-zA-Z0-9]+)$/);
		if(!m){return;}var rowid = m[1];var fieldName = m[2];
		var v = elem.innerHTML;
//FIXME: comprobar lastChild y que no sea el info
		var i = info_create('changeField',{ondblclick:function(e){e.stopPropagation();}},a).infoContainer.empty();
		$C('H6',{innerHTML:'Cambiando el valor del campo'},i);
		$C('INPUT',{name:'ids',type:'hidden',value:rowid},i);
		$C('INPUT',{name:'fieldName',type:'hidden',value:fieldName},i);
		var textarea = $C('TEXTAREA',{name:'fieldText',value:v},$C('DIV',{className:'inputTextSimple'},i));
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancelar',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('Aceptar',function(e){z(e,i,api,elem);},d,'assisButton');
		textarea.focus();

		function z(e,i,api,callbackElem){
			var p = extend($parseForm(i),{'command':'changeField'});
			ajaxPetition(api,$toUrl(p),function(ajax){var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
				for(i in r.data){var newData = r.data[i][p.fieldName];}
				callbackElem.innerHTML = newData;
				//alert(print_r(r));
			});
		}
	},
	searchAsURL: function(el,e){
		e.preventDefault();
		var a = el.parentNode;
		var i = info_create('search',{'.width':'300px'},a).infoContainer.empty();
		$C('DIV',{innerHTML:'Buscar'},i);
		var p = $C('INPUT',{name:'criteria',value:''},$C('DIV',{className:'inputText'},i));

		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancelar',function(e){e.preventDefault();info_destroy(i);return false;},d);
		gnomeButton_create('Aceptar',function(){z(el,p.value);},d);


		function z(el,v){var href = el.href;if(href.substr(-1) !== '/'){href += '/';}w(href+encodeURI(v));}
		return false;
	}
}
var link = {
	confirm: function(el,e){
		e.preventDefault();
		el.style.position = 'relative';
		var lnk = el.href;
		var i = info_create('confirm',{},el).infoContainer.empty();
		$C('DIV',{innerHTML:'Are you sure you want to proceed?'},i);
		var d = $C('UL',{className:'buttonHolder'},i);
		gnomeButton_create('Cancel',function(e){e.preventDefault();info_destroy(i);return false;},d,'assisButton');
		gnomeButton_create('OK',function(){w(lnk);},d,'assisButton');
		return false;
	}
}
function w(loc){if(loc.substr(0,VAR_baseURL.length) == VAR_baseURL){loc = loc.substr(VAR_baseURL.length);}window.location = VAR_baseURL+loc;}
