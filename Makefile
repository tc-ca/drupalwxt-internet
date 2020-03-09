build:
	docker build -t drupalwxt-internet .

tag:
	docker tag drupalwxt-internet nprdcacnwwwdevacr.azurecr.io/drupalwxt-internet
	docker tag drupalwxt-internet nprdcacnwwwaccacr.azurecr.io/drupalwxt-internet
	docker tag drupalwxt-internet prodcacnwwwacr.azurecr.io/drupalwxt-internet

run:
	docker run -d \
	--name drupalwxt-internet \
	-p 80:80 \
	-h dev.tc.gc.ca \
	--network host \
	-v $(CURDIR)/docker/apache2/sites-available/vhost.conf:/etc/apache2/sites-available/000-default.conf \
	-v $(CURDIR)/docker/php/php.ini:/usr/local/etc/php/php.ini \
	-v $(CURDIR)/docker/startup/init.sh:/usr/local/bin/init.sh \
	-v $(CURDIR)/storage/home:/home \
	-v $(CURDIR)/tcwww:/var/www \
	-e DRUPAL_HASH_SALT='PHlhk1pNA3I-ifkIF93PaDfVbX47lddV-1v5pNOLVV83aYct4sg8OIaaRDeXvSlAUzlD9hlq2w' \
	-e POSTGRES_DATABASE=www \
	-e POSTGRES_USERNAME=postgres \
	-e POSTGRES_PASSWORD=WxT \
	-e POSTGRES_HOST=localhost \
	drupalwxt-internet:latest
	
	docker run -d \
	--name postgres-drupalwxt \
	-p 5432:5432 \
	-e POSTGRES_PASSWORD=WxT \
	-v pgdatawxt:/var/lib/postgresql/data \
	postgres:11.6

	docker ps -a
	
stop:
	docker stop drupalwxt-internet
	docker stop postgres-drupalwxt
	docker rm drupalwxt-internet
	docker rm postgres-drupalwxt

login:
	az acr login --name nprdcacnwwwdevacr

login_acc:
	az acr login --name nprdcacnwwwaccacr

login_prod:
	az acr login --name prodcacnwwwacr

push:
	docker push nprdcacnwwwdevacr.azurecr.io/drupalwxt-internet:latest

push_acc:
	docker push nprdcacnwwwaccacr.azurecr.io/drupalwxt-internet:latest

push_prod:
	docker push prodcacnwwwacr.azurecr.io/drupalwxt-internet:latest

bash:
	docker exec -it drupalwxt-internet /bin/bash