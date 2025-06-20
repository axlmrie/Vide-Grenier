FROM mysql:latest

COPY ./import.sql /docker-entrypoint-initdb.d/

RUN chmod +x /docker-entrypoint-initdb.d/import.sql
