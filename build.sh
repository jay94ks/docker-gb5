#!/bin/bash

docker build -t jay94ks/docker-gb5:v20220726 .
docker push jay94ks/docker-gb5:v20220726

docker tag jay94ks/docker-gb5:v20220726 jay94ks/docker-gb5:latest
docker push jay94ks/docker-gb5:latest

