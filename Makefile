develop:
	docker compose run --rm -u 1000 development bash

bash:
	docker compose exec -it development bash