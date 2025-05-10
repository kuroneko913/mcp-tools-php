IMAGE_NAME = mcp-tools-php

.PHONY: build run clean shell help

build:
	docker build -f docker/Dockerfile -t $(IMAGE_NAME) .

run:
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

help:
	@echo "make build   # Dockerイメージをビルド"
	@echo "make run     # サーバを起動（.envがあれば自動で読み込む）"
	@echo "make clean   # イメージを削除"
	@echo "make shell   # コンテナ内シェルに入る"
	@echo "make help    # このヘルプを表示"
