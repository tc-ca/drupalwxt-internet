push_covid:
	az acr login --name devtestwwwacr
	docker tag drupalwxt-internet devtestwwwacr.azurecr.io/drupalwxt-internet
	docker push devtestwwwacr.azurecr.io/drupalwxt-internet:latest

push_dev:
	az acr login --name nprdcacnwwwdevacr
	docker tag drupalwxt-internet nprdcacnwwwdevacr.azurecr.io/drupalwxt-internet
	docker push nprdcacnwwwdevacr.azurecr.io/drupalwxt-internet:latest

push_acc:
	az acr login --name nprdcacnwwwaccacr
	docker tag drupalwxt-internet nprdcacnwwwaccacr.azurecr.io/drupalwxt-internet
	docker push nprdcacnwwwaccacr.azurecr.io/drupalwxt-internet:latest

push_prod:
	az acr login --name prodcacnwwwacr
	docker tag drupalwxt-internet prodcacnwwwacr.azurecr.io/drupalwxt-internet
	docker push prodcacnwwwacr.azurecr.io/drupalwxt-internet:latest

bash:
	docker exec -it drupalwxt-internet /bin/bash