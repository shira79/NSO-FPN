FROM php:8.0-fpm

RUN apt update
RUN apt install -y cron

COPY src/cron /var/spool/crontab/root
RUN crontab /var/spool/crontab/root

CMD cron -f