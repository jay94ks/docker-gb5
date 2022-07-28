
docker build -t jay94ks/docker-gb5:v20220728-auto-eb4 .
docker push jay94ks/docker-gb5:v20220728-auto-eb4

docker tag jay94ks/docker-gb5:v20220728-auto-eb4 jay94ks/docker-gb5:latest-auto-eb4
docker push jay94ks/docker-gb5:latest-auto-eb4

