function route(uri, params) {
	if(uri[0]!='/') uri='/'+uri;
	for(p in params) {
		uri=uri.replace(new RegExp('\{'+p+'\??\}','g'),params[p]);
	}
	return uri;
}