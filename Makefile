develop:
	docker compose run --rm -u 1000 development bash

bash:
	docker compose exec -it development bash

release:
	git push
	git checkout main
	git merge develop
	git push
	git checkout develop

# Production compose helpers
prod-build:
	docker compose -f docker-compose.prod.yaml build

prod-up:
	docker compose -f docker-compose.prod.yaml up -d

prod-down:
	docker compose -f docker-compose.prod.yaml down

prod-restart: prod-down prod-up

prod-logs:
	docker compose -f docker-compose.prod.yaml logs -f