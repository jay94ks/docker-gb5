#!/bin/bash

docker build -t jay94ks/docker-gb5:v20220727_1-auto .
docker push jay94ks/docker-gb5:v20220727_1-auto

docker tag jay94ks/docker-gb5:v20220727_1-auto jay94ks/docker-gb5:latest-auto
docker push jay94ks/docker-gb5:latest-auto

