tag:
	docker tag drupalwxt-internet nprdcacnwwwdevacr.azurecr.io/drupalwxt-internet
	docker tag drupalwxt-internet nprdcacnwwwaccacr.azurecr.io/drupalwxt-internet
	docker tag drupalwxt-internet prodcacnwwwacr.azurecr.io/drupalwxt-internet

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