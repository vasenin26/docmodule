develop:
	docker compose run --rm -u 1000 development bash

bash:
	docker compose exec -it development bash

release:
	git push
	git checkout main
	git merge develop
	make bump-minor
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

.PHONY: bump-patch
bump-patch:
	@if [ "$$(git branch --show-current)" != "main" ]; then \
		echo "Ошибка: Создание патч-версии возможно только на ветке main"; \
		exit 1; \
	fi
	$(eval LATEST_TAG := $(shell git describe --tags --abbrev=0))

	$(eval MAJOR := $(word 1,$(subst ., ,$(LATEST_TAG:v%=%))))
	$(eval MINOR := $(word 2,$(subst ., ,$(LATEST_TAG))))
	$(eval PATCH := $(word 3,$(subst ., ,$(LATEST_TAG))))

	$(eval NEW_TAG := v$(MAJOR).$(MINOR).$(shell echo $$(($(PATCH)+1))))

	@echo "Текущий тег: $(LATEST_TAG)"
	@echo "Новый тег:   $(NEW_TAG)"

	git tag -a $(NEW_TAG) -m "Bump version to $(NEW_TAG)"

	git push origin $(NEW_TAG)

.PHONY: bump-minor
bump-minor:
	@if [ "$$(git branch --show-current)" != "main" ]; then \
		echo "Ошибка: Создание минор-версии возможно только на ветке main"; \
		exit 1; \
	fi
	$(eval LATEST_TAG := $(shell git describe --tags --abbrev=0))

	$(eval MAJOR := $(word 1,$(subst ., ,$(LATEST_TAG:v%=%))))
	$(eval MINOR := $(word 2,$(subst ., ,$(LATEST_TAG))))

	$(eval NEW_TAG := v$(MAJOR).$(shell echo $$(($(MINOR)+1))).0)

	@echo "Текущий тег: $(LATEST_TAG)"
	@echo "Новый тег:   $(NEW_TAG)"

	git tag -a $(NEW_TAG) -m "Bump minor version to $(NEW_TAG)"

	git push origin $(NEW_TAG)