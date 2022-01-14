ARG TAG

FROM debian:buster-20210927-slim AS base
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN set -eux; \
	apt-get update; \
	apt-get install -y \
		webdis;

COPY ./infra/webdis/webdis.json /etc/webdis/webdis.json

WORKDIR /etc/webdis

CMD ["webdis"]

FROM base AS test
FROM base AS dev
