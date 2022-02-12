FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive
ENV LC_ALL=C.UTF-8

RUN apt-get update
RUN apt-get install -y \
    git \
    openjdk-11-jdk \
    && rm -rf /var/lib/apt/lists/*

ENV JAVA_HOME=/usr/lib/jvm/java-11-openjdk-amd64

RUN apt-get install -y \
    curl \
    && curl -fsSL https://uisp.ui.com/v1/install > /tmp/uisp_inst.sh \
    && sudo bash /tmp/uisp_inst.sh \
