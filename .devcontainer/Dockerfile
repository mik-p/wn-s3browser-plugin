FROM ghcr.io/mpo-web-consulting/wintercms:latest

ENV DEBIAN_FRONTEND=noninteractive

USER root

ARG UID=1000
ARG GID=1000

# add dev packages
RUN apt-get update && \
    apt-get upgrade -y --no-install-recommends && \
    apt-get -y install --no-install-recommends \
    sudo \
    nano \
    git \
    openssh-client \
    iproute2 \
    procps \
    lsb-release \
    curl \
    wget \
    unzip \
    apt-transport-https \
    dialog \
    gnupg2 \
    ca-certificates && \
    apt-get autoremove -y && \
    apt-get clean -y && \
    rm -rf /var/lib/apt/lists/*

# install xdebug
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# add vscode user with UID, and GID matching that of the host user
RUN groupadd -g $GID vscode && \
    useradd -s /bin/bash --uid $UID --gid $GID -m vscode && \
    usermod -aG sudo vscode && \
    usermod -aG www-data vscode && \
    echo "vscode ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers.d/vscode && \
    echo "for bcfile in /usr/share/bash-completion/completions/*; do . \$bcfile; done" >> /home/vscode/.bashrc

# switch to vscode user
USER vscode
