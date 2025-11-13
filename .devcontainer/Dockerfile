FROM mcr.microsoft.com/devcontainers/php:8.3

# Install system dependencies for PostgreSQL
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install Postgres client
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y postgresql-client \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install php-pgsql driver
RUN docker-php-ext-install pdo pdo_pgsql pcntl

# [Optional] Uncomment this section to install additional OS packages.
# RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
#     && apt-get -y install --no-install-recommends <your-package-list-here>

# [Optional] Uncomment this line to install global node packages.
# RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g <your-package-here>" 2>&1

