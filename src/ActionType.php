<?php

namespace ArsDigitalia;

class ActionType extends Enum {

    const PUSH = 'Push';
    const PULL_REQUEST_CREATED = 'Pull request (created)';
    const PULL_REQUEST_UPDATED = 'Pull request (updated)';

}