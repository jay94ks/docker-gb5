## 그누보드 PHP + Apache2 컨테이너

### `auto-eb4` branch
이 branch는 그누보드 깃허브 및 이윰빌더 시즌4 깃허브에서 파일을 동기화 받으며,
자동으로 설치하는 것 까지를 수행하는 컨테이너 이미지입니다.

사용할 그누보드 버전은 아래처럼 지정합니다.
(https://github.com/gnuboard/gnuboard5 내 `tags` 참조)
(https://github.com/eyoom/eyoom_builder_4 참조, 버전 태깅을 사용하지 않는 리포지터리, 즉, 항상 최신으로 설치됩니다)

* 본 도커 이미지를 사용하기 전 반드시 사용권 계약을 읽어보시길 바랍니다.
-> https://eyoom.net/page/eb4_license (그누보드와 달리 eb4는 LGPLv2.1이 아닙니다!)
```
docker ..... -e G5_GIT_TAG=v5.5.8.2 .....
```

`docker-compose.yml` 사용시 (샘플 파일은 test 디렉토리에 있습니다)
```
web:
	...............
	environment:
		- G5_GIT_TAG=v5.5.8.2   # (기본값)

    # 현재 개발중인 branch를 사용하려면 master를 지정하면 되지만,
    # master 브랜치는 preview 목적으로만 사용하시고,
    # 안정 버전으로 지목된 branch를 사용하시길 권장드립니다.
    # -----
    # eb4가 정상적으로 동작하려면,
    # https://eyoom.net/ 에서 공지한 g5 버전으로 맞춰 지정해 주시기 바랍니다.
```

자동 설치 인수는 환경변수로 전달하며, 아래와 같습니다.
또한, 한번 설치가 진행된 이후엔 아래 인수들을 모두 `docker-compose.yml` 파일에서 제거해도 좋습니다.
```
1. 필수 인수
  G5_MYSQL_HOST
  G5_MYSQL_USER
  G5_MYSQL_PASSWORD
  G5_MYSQL_DB

2. 옵션             (: 기본값 -> 입력 형식)
  G5_TABLE_PREFIX   (: g5_)
  G5_ADMIN_ID       (: admin)
  G5_ADMIN_PASSWORD (: abcd1234)
  G5_ADMIN_NAME     (: 최고관리자)
  G5_ADMIN_EMAIL    (: admin@localhost)
  G5_SHOP_PREFIX    (: yc5_)
  G5_SHOP_INSTALL   (: 'yes' -> {y, yes} or {n, no})
  EB4_RM_LEGAL_INFO (: 'no' -> {y, yes} or {n, no})
  * --> 이 옵션은 설치 완료 후에 eyoom/LICENSE.txt 파일을 삭제할지 여부입니다.
  G5_RM_LEGAL_INFO  (: 'no' -> {y, yes} or {n, no})
  * --> 이 옵션은 설치 완료 후에 LICENSE.txt, README.md, perms.sh 파일을 삭제할지 여부입니다.

  G5_RM_IMORT_UTILS (: 'no' -> {y, yes} or {n, no})
  * --> 이 옵션은 설치 완료 후에 g4_import{,_run}.php, yc4_import{,_run}.php 파일을 삭제할지 여부입니다.
  G5_RM_YC_OLD_UTIL (: 'no' -> {y, yes} or {n, no})
  * --> 이 옵션은 orderupgrade.php 파일을 삭제할지 여부입니다.
  * --> v5.5.8.2에서는 이미 적용된 상태이므로 신규 설치시에는 불필요합니다.
```

또한, 본 컨테이너 이미지는 아래의 특수 파일들을 생성합니다.
그러므로, 호스트 볼륨을 마운팅 하실때 `/var/www` 디렉토리 자체를 마운팅 해주십시오.
```
/var/www/run  : 그누보드5 설치 정보
  - g5-git    : 설치한 git branch tag, 즉, 버전을 기록.
  - g5-www    : 그누보드가 설치되었다고 플래그를 기록.
  * 이 파일들을 삭제할 경우, 그누보드를 완전히 새로 설치하게 됩니다. (기존 파일을 덮어 씁니다)
  * 여러 안전 조치를 수행하긴 하지만, 위 파일들을 삭제하지 않도록 주의해 주십시오.
    - data 디렉토리의 dbconfig.php 파일 존재 유무 검사.
    - 컨테이너 자체 /root/install-auto.php 파일.
      * 자동 설치가 정상적으로 완료되면 이 파일이 삭제되며,
      + 위 파일들이 모두 손상된 상태에서 컨테이너가 실행되면,
      + install-auto.php 파일이 존재하지 않으면,
        아래 메시지를 출력하며 더 진행되어 손상되지 않도록 막습니다.
      : >> fatal: the g5 installation maybe corrupted.

/var/www/html : 그누보드가 설치되는 경로.
```

GIT 혹은 SVN으로 운영중인 사이트를 복제하도록 구성하려면, 아래를 참고해 주세요.
이 단락에서는 볼륨을 마운팅하지 않는 것을 기본 전제로 깔고 갑니다.

1. Dockerfile 작성하기.
```
FROM jay94ks/docker-g5:latest-auto
ENV G5_GIT_TAG=v5.5.8.2

# 내장시킬 사이트 파일들을 기본 이미지에 복사합니다.
# 복사되는 순서는 git clone 직후이며, auto-install 스크립트가 실행 되기 전입니다.
# 혹은, git, svn 등으로 동기화를 받도록 만들수도 있겠습니다.
# 그리고, /apps/ 경로에 site-init.sh파일을 만들면,
# 최초 컨테이너 시작시 그걸 실행시키고,
# site-up.sh 파일을 만들면, 매 컨테이너 실행시 마다 실행시켜 줍니다. (root 권한)
COPY ./site/ /apps/html

# 이 부분은 auto-install 스크립트 실행을 차단시킵니다.
# 사이트 파일에 dbconfig.php 파일이 존재하고, 이미 운영중인
# MySQL을 사용한다면 이 부분이 반드시 필요합니다.
RUN mkdir -pv /var/www/run && touch /var/www/run/g5-www \
 && rm -rf /root/install-auto.php

# 커스텀 초기화 스크립트를 사용하는 경우,
COPY ./custom-init.php /root/custom-init.php

ENTRYPOINT [ "/root/entry.sh" ]
CMD [ "/root/custom-init.php" ]
```

### 사용법
본 컨테이너는 그누보드5 혹은 영카트5를 지원하며,
파생 빌더의 경우, `dbconfig.php` 파일을 다른 위치에 배치하거나,
설정값 명칭이 변경된 경우 호환되지 않습니다.

1. 그누보드 다운로드: https://sir.kr/main/g5/ (공식 웹사이트)
2. 다운로드 받으신 후 압축 풀어서 컨테이너 `/var/www/html` 경로에 볼륨으로 마운트시켜 줍니다.

`Dockerfile`을 빌드하고, 테스트 런 하는 명령은 아래와 같습니다.
```
docker build -t docker-gb5 .
docker run --rm --name gb5_test -p 80:80 -v ./www:/var/www/html docker-gb5
```

`docker-compose`를 이용한 방법은 아래와 같습니다.
```
version: '3.4'
services:
  web:
    image: 'jay94ks/docker-gb5'
    build: # <-- 이 섹션은 미리 빌드된 이미지를 사용하지 않을 때만 지정해 주세요.
      context: .
      dockerfile: Dockerfile
    container_name: 'web'
    volumes:
      - "./web/www:/var/www/html"
    ports: 
        - 80:80 # 기본 포트가 아닌 다른 포트로 사용하시려면 포트 번호를 변경해 주세요.
    links: 
        - 'web-db'
        
  web-db:
    image: 'mysql:latest'
    container_name: 'web-db'
    restart: always
    environment:
      MYSQL_DATABASE: '<생성될 DB 명>'
      MYSQL_USER: '<생성될 DB USER 명>'
      MYSQL_PASSWORD: '<생성될 DB 패스워드>'
      MYSQL_ROOT_PASSWORD: '<생성될 DB ROOT 패스워드>' # DB 루트 계정이 필요하지 않다면 지정하지 마세요.
    volumes:
      - './web/db:/var/lib/mysql'
    ports: # DB에 직접 접근해서 볼 수 있어야 하는게 아니라면 지정하지 마세요.
      - '3306:3306'
    command:
      - '--character-set-client-handshake=FALSE'
      - '--character-set-server=utf8mb4'
      - '--collation-server=utf8mb4_unicode_ci'
```

커스텀 초기화 스크립트 실행하는 방법.
```
web:
	...............
	command:
		- "my_init.php"
	
	# 기준 경로: /var/www/html
	# 웹에서 접근이 되지 말아야 하는 경우엔,
	# 아래처럼 작업해 주세요.
	
	volumes:
      - "./hidden:/root/hidden"
	  
	command:
		- "/root/hidden/my-init.php"

```

### 아파치 설정값
본 컨테이너는 아파치 설정 수준에서 아래 디렉토리 및 파일들에 대한 접근을 차단하거나 변경합니다.

1. SVN/GIT 버전 컨트롤 디렉토리 (`.svn`, `.git`)
2. `.`으로 시작되는 숨김 파일, `.bkp`로 끝나는 파일
3. `.exe`, `.dll`, `.sys`, `.obj`, `.pdb` 등 윈도우에서 실행 가능한 파일.
4. `/var/www/html` 디렉토리에 대한 디렉토리 인덱싱.

(번외) 그누보드 데이터 디렉토리에 대한 접근 제어는
`.htaccess`파일을 자동으로 생성하여 수행하며, 다음과 같습니다.

1. `.cgi`, `.pl`, `.php`, `.html`, `.htm` 파일 접근 차단.
2. `dbconfig.php` 파일 접근 차단.
3. `.htaccess` 파일 자체의 권한을 `0644`로 조정.

### PHP 설정값
본 컨테이너는 PHP를 `cli` 및 `www`로 나눠 설정하고 있으며,
아래는 `ubuntu:20.04`의 기본 `php7.4` 패키지 내에서 변경된 설정 값들 입니다.

<b>php-cli.ini (초기화 스크립트 실행 환경)</b>
```
;;;;;;;;;;;;;;;;;;;
; Resource Limits ;
;;;;;;;;;;;;;;;;;;;

; Maximum execution time of each script, in seconds
; http://php.net/max-execution-time
; Note: This directive is hardcoded to 0 for the CLI SAPI
; max_execution_time = 30

; Maximum amount of time each script may spend parsing request data. It's a good
; idea to limit this time on productions servers in order to eliminate unexpectedly
; long running scripts.
; Note: This directive is hardcoded to -1 for the CLI SAPI
; Default Value: -1 (Unlimited)
; Development Value: 60 (60 seconds)
; Production Value: 60 (60 seconds)
; http://php.net/max-input-time
; max_input_time = 60

; Maximum input variable nesting level
; http://php.net/max-input-nesting-level
;max_input_nesting_level = 64

; How many GET/POST/COOKIE input variables may be accepted
;max_input_vars = 1000

; Maximum amount of memory a script may consume (128MB)
; http://php.net/memory-limit
memory_limit = -1
```

<b>php-www.ini (웹 서버 - 그누보드 동작 환경)</b>
```
;;;;;;;;;;;;;;;;
; File Uploads ;
;;;;;;;;;;;;;;;;

; Whether to allow HTTP file uploads.
; http://php.net/file-uploads
file_uploads = On

; Temporary directory for HTTP uploaded files (will use system default if not
; specified).
; http://php.net/upload-tmp-dir
;upload_tmp_dir =

; Maximum allowed size for uploaded files.
; http://php.net/upload-max-filesize
upload_max_filesize = 32M

; Maximum number of files that can be uploaded via a single request
max_file_uploads = 20

.......................

; Maximum size of POST data that PHP will accept.
; Its value may be 0 to disable the limit. It is ignored if POST data reading
; is disabled through enable_post_data_reading.
; http://php.net/post-max-size
post_max_size = 32M
```

### 초기화 스크립트
초기화 스크립트는 아래와 같은 동작을 목표로 작성되었습니다.

```
1. `/var/www/html/data` 디렉토리 생성 및 권한 설정
2. MySQL가 접속 가능한 상태가 될 때 까지 대기처리. (dbconfig.php 파일이 존재하면)
```