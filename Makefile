IMAGE_NAME = mcp-tools-php

.PHONY: build run clean shell test lint analyse check help

build:
	docker build -f docker/Dockerfile -t $(IMAGE_NAME) .

run: build
	@if [ -f .env ]; then \
		echo "[INFO] .envを検出: --env-file .env で起動"; \
		docker run --env-file .env -i $(IMAGE_NAME); \
	else \
		docker run -i $(IMAGE_NAME); \
	fi

clean:
	docker rmi $(IMAGE_NAME) || true

exec:
	docker run --rm -it $(IMAGE_NAME) bash

test:
	vendor/bin/phpunit

lint:
	vendor/bin/phpcs

analyse:
	vendor/bin/phpstan analyse --memory-limit=2G

check: test lint analyse

help:
	@echo "make build   # Dockerイメージをビルド"
	@echo "make run     # サーバを起動（.envがあれば自動で読み込む）"
	@echo "make clean   # イメージを削除"
	@echo "make shell   # コンテナ内シェルに入る"
	@echo "make test    # PHPUnitを実行"
	@echo "make lint    # PHP_CodeSnifferを実行"
	@echo "make analyse # PHPStanを実行"
	@echo "make check   # test, lint, analyseを順に実行"
	@echo "make help    # このヘルプを表示"
