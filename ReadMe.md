## 그누보드 PHP + Apache2 컨테이너

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
    image: 'docker-gb5'
    build: # <-- 이 섹션은 미리 빌드된 이미지를 사용하지 않을 때만 지정해 주세요.
      context: .
      dockerfile: Dockerfile
    container_name: 'web'
    volumes:
      - "./www:/var/www/html"
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
      - '/web/db:/var/lib/mysql'
    ports: # DB에 직접 접근해서 볼 수 있어야 하는게 아니라면 지정하지 마세요.
      - '3306:3306'
    command:
      - '--character-set-client-handshake=FALSE'
      - '--character-set-server=utf8mb4'
      - '--collation-server=utf8mb4_unicode_ci'
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