var storageEngine = YAHOO.util.StorageManager.get(
	YAHOO.util.StorageEngineHTML5.ENGINE_NAME,
	YAHOO.util.StorageManager.LOCATION_LOCAL,
	{
		force: false,
		order: [
			YAHOO.util.StorageEngineGears,
			YAHOO.util.StorageEngineSWF
		]
	}
);

//var storageEngine = YAHOO.util.StorageManager.get();

function wgs_set(key, value){
	storageEngine.subscribe(storageEngine.CE_READY, function() {
		storageEngine.setItem(key, value);
	});
}

function wgs_get(key){
	var value = storageEngine.getItem(key);
	return value;
}
