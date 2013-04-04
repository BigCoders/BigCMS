var cart = {
	vars: {h:"",ul:""},
	addItemToCart: function(encodedItem){
		var item = jsonDecode(item);
		cookieSet('cartItem_'+item.id,encodedItem,30);
	},
	addItemToCartFromArray: function(e,arrName,index){
		if(!window[arrName] || !window[arrName][index]){return false;}
		var encodedItem = window[arrName][index];
		encodedItem = encodedItem.replace(/,\"productDescription\":\"[^\"]*\"/,'');
		var item = jsonDecode(encodedItem);
		item.productDescription = false;
		item.productCount = 1;
		encodedItem = jsonEncode(item);
		cookieSet('cartItem_'+item.id,encodedItem,30);
		if(window.custom && window.custom.onProductAdd){window.custom.onProductAdd(e,arrName,index);}

		this.helper_updateFields();
		return true;
	},
	item_changeCount: function(index,count,arrName){
		var rawCookie = cookieTake('cartItem_'+index);
		var encodedItem = false;
		if(rawCookie !== false){encodedItem = unescape(rawCookie);}
		/*Intentamos coger el producto del array*/
		if(rawCookie === false && arrName && window[arrName] && window[arrName][index]){encodedItem = unescape(window[arrName][index]);}
		if(encodedItem === false){return;}
		var item = jsonDecode(encodedItem);

		if(count < 1){this.item_removeFromCart(index);return;}
		item.productCount = count;
		encodedItem = jsonEncode(item);
		cookieSet('cartItem_'+item.id,encodedItem,30);

		this.helper_updateFields();
	},
	item_removeFromCart: function(id,elem){
		cookieSet('cartItem_'+id,'',-1);
		if(elem){
			while(elem.parentNode && !elem.className.match(/productNode/)){elem = elem.parentNode;}
			if(elem.className.match(/removable/)){elem.parentNode.removeChild(elem);}
		}

		this.helper_updateFields();
	},
	helper_updateFields: function(){
		//FIXME: calcular el precio en este bucle
		var currentCookies = cookiesToObj();
		var productsCount = 0;
		var productsPrice = 0;
		for(var a in currentCookies){
			if(!a.match(/cartItem_[0-9]+/)){continue;}
			productsCount++;
			var item = jsonDecode(currentCookies[a]);
			productsPrice += (item.productPrice*item.productCount);
		}

		productsPrice = $round(productsPrice);
		if(window.custom && window.custom.fields_updatableCount){var e = false;$A(window.custom.fields_updatableCount).each(function(elem){if(e = $_(elem)){e.innerHTML = productsCount;}});}
		if(window.custom && window.custom.fields_updatablePrice){var e = false;$A(window.custom.fields_updatablePrice).each(function(elem){if(e = $_(elem)){e.innerHTML = productsPrice;}});}
	}
};
