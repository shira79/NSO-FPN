<?php

interface Notification {
    public function setParameter(array $parameter);
    public function send();
}