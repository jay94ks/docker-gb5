#!/bin/bash

docker build -t jay94ks/docker-gb5:v20220725 .
docker push jay94ks/docker-gb5:v20220725

docker tag jay94ks/docker-gb5:v20220725 jay94ks/docker-gb5:latest
docker push jay94ks/docker-gb5:latest

